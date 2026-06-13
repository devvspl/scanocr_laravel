<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Import\ImportJob;
use App\Models\Import\ImportTemplate;
use App\Models\Import\ImportRow;
use App\Models\Import\ApiConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    // ─── Index / History ────────────────────────────────────────────────
    public function index()
    {
        $company = Company::getDefault();
        
        $jobs = ImportJob::where('company_id', $company?->id)
            ->with('createdBy', 'template')
            ->latest()
            ->paginate(20);

        return view('panel.import.index', compact('jobs', 'company'));
    }

    // ─── Upload & Preview ───────────────────────────────────────────────
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'file'        => 'required|file|mimes:xlsx,xls,csv,sql,txt|max:51200',
                'source_type' => 'required|in:excel,csv,sql',
                'table_name'  => 'nullable|string',
            ]);

            $file = $request->file('file');
            $uuid = (string) Str::uuid();
            $ext  = $file->getClientOriginalExtension();
            $filename = $uuid . '.' . $ext;

            // Store file using Laravel's storage (local disk uses app/private)
            // So we'll use public disk or store directly
            $storagePath = storage_path('app/imports');
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0755, true);
            }
            
            $file->move($storagePath, $filename);
            $fullPath = $storagePath . DIRECTORY_SEPARATOR . $filename;
            
            \Log::info('File uploaded', ['path' => $fullPath, 'exists' => file_exists($fullPath)]);

            // Verify file exists
            if (!file_exists($fullPath)) {
                \Log::error('File not found after upload', ['path' => $fullPath]);
                return response()->json(['error' => 'File upload verification failed'], 500);
            }

            // Extract headers based on source type
            $headers = [];
            try {
                $headers = match($request->source_type) {
                    'excel' => $this->getExcelHeaders($fullPath),
                    'csv'   => $this->getCsvHeaders($fullPath),
                    'sql'   => $this->getSqlHeaders($fullPath),
                    default => [],
                };
            } catch (\Exception $e) {
                \Log::error('Failed to read file headers', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Failed to read file: ' . $e->getMessage()], 422);
            }

            return response()->json([
                'uuid'       => $filename,
                'headers'    => $headers,
                'extension'  => $ext,
                'table_name' => $request->table_name,
                'file_url'   => url('master/import/preview/' . $filename),
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Upload error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    // ─── Get Available Tables (for generic import) ──────────────────────
    public function getTables()
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $database = config('database.connections.mysql.database');
            $tableKey = "Tables_in_{$database}";
            
            $tableList = array_map(function($table) use ($tableKey) {
                // Convert object to array to handle dynamic property
                $tableArray = (array) $table;
                return $tableArray[$tableKey] ?? current($tableArray);
            }, $tables);

            return response()->json(['tables' => array_values($tableList)]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'tables' => []], 500);
        }
    }

    // ─── Get Table Columns (for mapping) ────────────────────────────────
    public function getTableColumns(Request $request)
    {
        $request->validate(['table' => 'required|string']);
        
        // Check if table exists
        $tableExists = DB::select("SHOW TABLES LIKE '{$request->table}'");
        
        if (empty($tableExists)) {
            // Table doesn't exist - return empty array
            // This is OK for new table creation
            return response()->json(['columns' => []]);
        }
        
        $columns = DB::select("SHOW COLUMNS FROM {$request->table}");
        $columnList = array_map(fn($col) => $col->Field, $columns);

        return response()->json(['columns' => $columnList]);
    }

    // ─── Start Import ────────────────────────────────────────────────────
    public function start(Request $request)
    {
        $validated = $request->validate([
            'uuid'           => 'required|string',
            'source_type'    => 'required|string',
            'table_name'     => 'required|string', // Target table for import
            'column_mapping' => 'required|array',  // {source_col: target_col}
            'column_aliases' => 'nullable|array',  // {source_col: alias_name}
            'options'        => 'nullable|array',
            'template_name'  => 'nullable|string',
        ]);

        $company = Company::getDefault();
        if (!$company) {
            return response()->json(['error' => 'No company selected'], 422);
        }

        // Optionally save as template
        $templateId = null;
        if ($request->filled('template_name')) {
            $template = ImportTemplate::create([
                'company_id'     => $company->id,
                'name'           => $request->template_name,
                'data_type'      => $request->table_name,
                'source_type'    => $request->source_type,
                'column_mapping' => $request->column_mapping,
                'has_header_row' => $request->options['has_header_row'] ?? true,
                'delimiter'      => $request->options['delimiter'] ?? ',',
                'created_by'     => auth()->id(),
            ]);
            $templateId = $template->id;
        }

        // Create import job
        $job = ImportJob::create([
            'company_id'        => $company->id,
            'template_id'       => $templateId,
            'job_uuid'          => Str::uuid(),
            'data_type'         => $request->table_name,
            'source_type'       => $request->source_type,
            'source_identifier' => $request->uuid,
            'status'            => 'pending',
            'column_aliases'    => $request->column_aliases ?? [],
            'options'           => array_merge($request->options ?? [], [
                'column_mapping' => $request->column_mapping,
                'file_uuid'      => $request->uuid,
                'on_conflict'    => $request->options['on_conflict'] ?? 'skip',
            ]),
            'created_by'        => auth()->id(),
        ]);

        // Dispatch job
        \App\Jobs\ProcessImportJob::dispatch($job);

        return response()->json([
            'job_id'   => $job->id,
            'job_uuid' => $job->job_uuid,
            'message'  => 'Import started',
        ]);
    }

    // ─── Job Status (polling) ────────────────────────────────────────────
    public function status(ImportJob $job)
    {
        if ($job->company_id !== Company::getDefault()?->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'status'         => $job->status,
            'total_rows'     => $job->total_rows,
            'processed_rows' => $job->processed_rows,
            'success_rows'   => $job->success_rows,
            'failed_rows'    => $job->failed_rows,
            'skipped_rows'   => $job->skipped_rows,
            'progress'       => $job->progress_percent,
        ]);
    }

    // ─── Job Detail ──────────────────────────────────────────────────────
    public function show(ImportJob $job)
    {
        if ($job->company_id !== Company::getDefault()?->id) {
            abort(403);
        }

        $rows = $job->rows()->paginate(50);
        $company = Company::getDefault();
        
        return view('panel.import.show', compact('job', 'rows', 'company'));
    }

    // ─── Preview Uploaded File ───────────────────────────────────────────
    public function previewFile($filename)
    {
        $filePath = storage_path('app/imports/' . $filename);
        
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $mimeTypes = [
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls'  => 'application/vnd.ms-excel',
            'csv'  => 'text/csv',
            'sql'  => 'text/plain',
            'txt'  => 'text/plain',
        ];

        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';

        return response()->file($filePath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    // ─── Download Error Report ───────────────────────────────────────────
    public function downloadErrors(ImportJob $job)
    {
        if ($job->company_id !== Company::getDefault()?->id) {
            abort(403);
        }

        $failed = $job->rows()->where('status', 'failed')->get();
        $csv = "Row,Error,Raw Data\n";
        foreach ($failed as $row) {
            $csv .= "{$row->row_number},\"{$row->error_message}\",\"" . json_encode($row->raw_data) . "\"\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=import_errors_{$job->id}.csv",
        ]);
    }

    // ─── Delete Import Job ────────────────────────────────────────────────
    public function destroy(ImportJob $job)
    {
        if ($job->company_id !== Company::getDefault()?->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Delete associated rows
        $job->rows()->delete();
        
        // Delete the job
        $job->delete();

        return response()->json(['message' => 'Import job deleted successfully']);
    }

    // ─── Templates ────────────────────────────────────────────────────────
    public function templates()
    {
        $company = Company::getDefault();
        
        $templates = ImportTemplate::where('company_id', $company?->id)
            ->with('createdBy')
            ->latest()
            ->get();
            
        return response()->json($templates);
    }

    // ─── Delete Template ──────────────────────────────────────────────────
    public function deleteTemplate(ImportTemplate $template)
    {
        if ($template->company_id !== Company::getDefault()?->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $template->delete();
        return response()->json(['message' => 'Template deleted']);
    }

    // ─── API Connections ──────────────────────────────────────────────────
    public function apiConnections()
    {
        $company = Company::getDefault();
        
        $connections = ApiConnection::where('company_id', $company?->id)
            ->latest()
            ->get();
            
        return view('panel.import.api-connections', compact('connections', 'company'));
    }

    public function storeApiConnection(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'provider'          => 'required|string',
            'api_type'          => 'required|in:rest,graphql,soap,json-rpc,webhook,custom',
            'http_method'       => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'base_url'          => 'required|url',
            'endpoint'          => 'nullable|string',
            'auth_type'         => 'required|in:none,api_key,bearer,basic,oauth2,jwt,digest,custom_header',
            'auth_config'       => 'nullable|array',
            'headers'           => 'nullable|array',
            'query_params'      => 'nullable|array',
            'request_body'      => 'nullable|string',
            'response_format'   => 'required|in:json,xml,csv,text',
            'data_path'         => 'nullable|string',
            'pagination_type'   => 'required|in:none,offset,page,cursor,link',
            'pagination_config' => 'nullable|array',
            'timeout'           => 'required|integer|min:5|max:300',
            'verify_ssl'        => 'required|boolean',
            'data_type'         => 'required|string',
            'target_table'      => 'required|string',
            'create_table'      => 'required|boolean',
            'field_mapping'     => 'nullable|array',
            'sync_frequency'    => 'required|in:manual,15min,30min,hourly,daily,weekly',
        ]);

        $company = Company::getDefault();
        
        $connection = ApiConnection::create([
            ...$validated,
            'company_id' => $company->id,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API connection saved successfully',
            'connection' => $connection
        ]);
    }

    public function testApiConnection(ApiConnection $connection)
    {
        if ($connection->company_id !== Company::getDefault()?->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get($connection->base_url);
            return response()->json([
                'success' => $response->ok(),
                'status'  => $response->status(),
                'message' => $response->ok() ? 'Connection successful' : 'Connection failed'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage()
            ]);
        }
    }

    public function destroyApiConnection(ApiConnection $connection)
    {
        if ($connection->company_id !== Company::getDefault()?->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $connection->delete();
        return response()->json(['success' => true, 'message' => 'API connection deleted successfully']);
    }

    // ─── Helper Methods ───────────────────────────────────────────────────

    private function getExcelHeaders(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $ws = $spreadsheet->getActiveSheet();
        
        $headers = [];
        $highestColumn = $ws->getHighestColumn();
        $columnIndex = 'A';
        
        while ($columnIndex <= $highestColumn) {
            $value = $ws->getCell($columnIndex . '1')->getValue();
            if ($value) {
                $headers[] = $value;
            }
            $columnIndex++;
        }
        
        return $headers;
    }

    private function getCsvHeaders(string $filePath): array
    {
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle, 0, ',');
            fclose($handle);
            return $headers ?: [];
        }
        return [];
    }

    private function getSqlHeaders(string $filePath): array
    {
        // Extract column names from SQL INSERT statements
        $content = file_get_contents($filePath);
        
        if (preg_match("/INSERT INTO `?(\w+)`? \(([^)]+)\)/i", $content, $matches)) {
            $columns = array_map('trim', explode(',', $matches[2]));
            return array_map(fn($col) => trim($col, '`'), $columns);
        }
        
        return [];
    }
}
