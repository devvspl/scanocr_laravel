<?php

namespace App\Jobs;

use App\Models\Import\ImportJob;
use App\Models\Import\ImportRow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 min max
    public int $tries = 1;      // Don't retry partial imports

    public function __construct(public ImportJob $importJob) {}

    public function handle(): void
    {
        $this->importJob->update(['status' => 'processing', 'started_at' => now()]);

        try {
            // Create table if needed
            if ($this->importJob->options['create_table'] ?? false) {
                $this->createTable();
            }

            // Extract rows from source
            $rows = $this->extractRows();
            
            $this->importJob->update(['total_rows' => count($rows)]);

            // Process each row
            foreach ($rows as $index => $rawRow) {
                $this->processRow($rawRow, $index + 1);
            }

            // Determine final status
            $status = $this->importJob->failed_rows > 0
                ? ($this->importJob->success_rows > 0 ? 'partial' : 'failed')
                : 'completed';

            $this->importJob->update(['status' => $status, 'completed_at' => now()]);

        } catch (\Throwable $e) {
            $this->importJob->update([
                'status' => 'failed',
                'notes' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    private function extractRows(): array
    {
        $uuid = $this->importJob->options['file_uuid'] ?? $this->importJob->source_identifier;
        $path = storage_path('app/imports/' . $uuid);

        if (!file_exists($path)) {
            throw new \Exception("Import file not found: {$uuid} at {$path}");
        }

        return match($this->importJob->source_type) {
            'excel' => $this->extractExcel($path),
            'csv'   => $this->extractCsv($path),
            'sql'   => $this->extractSql($path),
            default => throw new \InvalidArgumentException("Unknown source: {$this->importJob->source_type}"),
        };
    }

    private function createTable(): void
    {
        $tableName = $this->importJob->data_type;
        
        // Add imp_ prefix if not already present
        if (!str_starts_with($tableName, 'imp_')) {
            $tableName = 'imp_' . $tableName;
            $this->importJob->update(['data_type' => $tableName]);
        }

        // Check if table already exists
        if (DB::select("SHOW TABLES LIKE '{$tableName}'")) {
            return; // Table already exists
        }

        // Get column mapping to determine columns
        $mapping = $this->importJob->options['column_mapping'] ?? [];
        
        if (empty($mapping)) {
            throw new \Exception("Cannot create table without column mapping");
        }

        // Create table with columns from mapping
        DB::statement("CREATE TABLE `{$tableName}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            " . implode(",\n            ", array_map(function($col) {
                return "`{$col}` TEXT NULL";
            }, array_values($mapping))) . ",
            `company_id` BIGINT UNSIGNED NULL,
            `created_by` BIGINT UNSIGNED NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            KEY `idx_company` (`company_id`),
            KEY `idx_created_by` (`created_by`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        \Log::info("Created table: {$tableName}");
    }

    private function extractExcel(string $path): array
    {
        $data = Excel::toArray([], $path);
        $sheet = $data[0] ?? [];

        $hasHeader = $this->importJob->options['has_header_row'] ?? true;
        if (!$hasHeader) return array_map(fn($r) => array_values($r), $sheet);

        $headers = array_shift($sheet);
        return array_map(function($row) use ($headers) {
            $row = array_pad($row, count($headers), null);
            return array_combine($headers, $row);
        }, $sheet);
    }

    private function extractCsv(string $path): array
    {
        $delimiter = $this->importJob->options['delimiter'] ?? ',';
        $hasHeader = $this->importJob->options['has_header_row'] ?? true;

        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $headers = $hasHeader ? fgetcsv($handle, 0, $delimiter) : null;
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rows[] = $headers ? array_combine($headers, $row) : $row;
            }
            fclose($handle);
        }
        return $rows;
    }

    private function extractSql(string $path): array
    {
        $content = file_get_contents($path);
        $rows = [];

        // Extract INSERT statements
        preg_match_all("/INSERT INTO `?(\w+)`? \(([^)]+)\) VALUES \(([^)]+)\)/i", $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $cols = array_map('trim', explode(',', $match[2]));
            $vals = array_map(fn($v) => trim($v, " '\""), explode(',', $match[3]));
            
            if (count($cols) === count($vals)) {
                $rows[] = array_combine($cols, $vals);
            }
        }

        return $rows;
    }

    private function processRow(array $rawRow, int $rowNum): void
    {
        $row = ImportRow::create([
            'import_job_id' => $this->importJob->id,
            'row_number'    => $rowNum,
            'raw_data'      => $rawRow,
            'status'        => 'pending',
        ]);

        try {
            // Map columns
            $mapped = $this->mapColumns($rawRow);
            $row->update(['mapped_data' => $mapped]);

            // Insert into target table
            $result = $this->insertIntoTable($mapped);

            $row->update([
                'status'       => 'success',
                'entity_id'    => $result['id'] ?? null,
                'action_taken' => $result['action'] ?? 'created',
            ]);

            $this->importJob->increment('processed_rows');
            $this->importJob->increment('success_rows');

        } catch (\Throwable $e) {
            $row->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            $this->importJob->increment('processed_rows');
            $this->importJob->increment('failed_rows');
        }
    }

    private function mapColumns(array $rawRow): array
    {
        $mapping = $this->importJob->options['column_mapping'] ?? [];
        if (empty($mapping)) return $rawRow;

        $mapped = [];
        foreach ($mapping as $sourceCol => $targetCol) {
            if (isset($rawRow[$sourceCol])) {
                $mapped[$targetCol] = $rawRow[$sourceCol];
            }
        }

        return $mapped;
    }

    private function insertIntoTable(array $data): array
    {
        $tableName = $this->importJob->data_type;
        $onConflict = $this->importJob->options['on_conflict'] ?? 'skip';

        // Add company_id if table has it
        if ($this->tableHasColumn($tableName, 'company_id')) {
            $data['company_id'] = $this->importJob->company_id;
        }

        // Add created_by if table has it
        if ($this->tableHasColumn($tableName, 'created_by')) {
            $data['created_by'] = $this->importJob->created_by;
        }

        // Add timestamps if table has them
        if ($this->tableHasColumn($tableName, 'created_at')) {
            $data['created_at'] = now();
            $data['updated_at'] = now();
        }

        // Check for duplicates (if unique key specified)
        $uniqueKey = $this->importJob->options['unique_key'] ?? null;
        if ($uniqueKey && isset($data[$uniqueKey])) {
            $existing = DB::table($tableName)
                ->where($uniqueKey, $data[$uniqueKey])
                ->where('company_id', $this->importJob->company_id)
                ->first();

            if ($existing) {
                return match($onConflict) {
                    'update' => $this->updateRecord($tableName, $existing->id, $data),
                    'skip'   => ['id' => $existing->id, 'action' => 'skipped'],
                    default  => $this->createRecord($tableName, $data),
                };
            }
        }

        return $this->createRecord($tableName, $data);
    }

    private function createRecord(string $table, array $data): array
    {
        $id = DB::table($table)->insertGetId($data);
        return ['id' => $id, 'action' => 'created'];
    }

    private function updateRecord(string $table, int $id, array $data): array
    {
        unset($data['created_at']); // Don't update created_at
        $data['updated_at'] = now();
        
        DB::table($table)->where('id', $id)->update($data);
        return ['id' => $id, 'action' => 'updated'];
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        static $cache = [];
        
        if (!isset($cache[$table])) {
            try {
                $columns = DB::select("SHOW COLUMNS FROM {$table}");
                $cache[$table] = array_map(fn($col) => $col->Field, $columns);
            } catch (\Exception $e) {
                // Table doesn't exist yet (new table being created)
                $cache[$table] = [];
            }
        }
        
        return in_array($column, $cache[$table]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->importJob->update([
            'status' => 'failed',
            'notes'  => $exception->getMessage(),
            'completed_at' => now(),
        ]);
    }
}
