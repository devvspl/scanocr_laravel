<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ExtractService
{
    /**
     * Get all pending queue items (with limit).
     */
    public function getPendingQueueItems(int $limit = 5): array
    {
        return DB::table('tbl_queues as q')
            ->join('scan_file as s', 's.Scan_Id', '=', 'q.scan_id')
            ->where('q.status', 'pending')
            ->where('s.File_Punched', 'N')
            ->orderBy('q.created_at', 'asc')
            ->limit($limit)
            ->select(['q.id', 'q.scan_id', 'q.type_id', 'q.status', 'q.created_by', 'q.created_at'])
            ->get()
            ->toArray();
    }

    /**
     * Get the API endpoint for a given document type.
     */
    public function getApiEndpoint(int $typeId): ?string
    {
        return DB::table('ext_mater_api_control')
            ->where('DocType_Id', $typeId)
            ->where('status', 1)
            ->value('endpoint');
    }

    /**
     * Get the file location (S3 URL) for a scan.
     */
    public function getFileLocation(int $scanId): ?string
    {
        return DB::table('scan_file')
            ->where('Scan_Id', $scanId)
            ->value('File_Location');
    }

    /**
     * Call the external extraction API.
     */
    public function callExternalApi(string $endpoint, string $fileUrl): array
    {
        try {
            $response = Http::timeout(120)
                ->post($endpoint, ['fileUrl' => $fileUrl]);

            return [
                'statusCode' => $response->status(),
                'data'       => $response->successful() ? $response->json() : null,
            ];
        } catch (\Exception $e) {
            Log::error('Extract API call failed', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return ['statusCode' => 500, 'data' => null];
        }
    }

    /**
     * Store extracted data into temp tables and move to punchfile.
     */
    public function storeExtractedData(int $typeId, int $scanId, array $data, ?int $classifiedBy, ?string $classifiedDate): bool
    {
        $tableName = 'ext_tempdata_' . $typeId;

        if (!Schema::hasTable($tableName)) {
            return false;
        }

        // Handle "Round Off" special case
        if (isset($data['Round Off']) && is_array($data['Round Off'])) {
            if (isset($data['Round Off']['Value'])) $data['Round Off Value'] = $data['Round Off']['Value'];
            if (isset($data['Round Off']['Type']))  $data['Round Off Type'] = $data['Round Off']['Type'];
            unset($data['Round Off']);
        }

        // Get Group_Id and Location from scan_file
        $scanData = DB::table('scan_file')
            ->where('Scan_Id', $scanId)
            ->select(['Group_Id', 'Location'])
            ->first();

        if (!$scanData) return false;

        // Delete existing temp data for this scan
        DB::table($tableName)->where('scan_id', $scanId)->delete();

        // Flatten the data
        $flatData = $this->flattenArray($data);

        // Get table columns
        $tableColumns = DB::getSchemaBuilder()->getColumnListing($tableName);

        // Build insert data
        $insertData = ['scan_id' => $scanId];

        foreach ($flatData as $key => $value) {
            if (is_array($value)) continue;

            $column = strtolower(str_replace([' ', '/', '-'], '_', $key));
            $matchedColumn = $this->getBestMatch($column, $tableColumns);

            if ($matchedColumn) {
                if (is_string($value) && $this->isValidDate($value) && stripos($key, 'date') !== false) {
                    $value = date('Y-m-d', strtotime($value));
                } else {
                    if (!is_null($value) && is_string($value)) {
                        $value = preg_replace('/[₹,]/', '', $value);
                    }
                    if (is_numeric($value)) {
                        $value = (float) $value;
                    }
                }
                $insertData[$matchedColumn] = $value;
            }
        }

        if (!DB::table($tableName)->insert($insertData)) {
            return false;
        }

        // Process details table if exists
        $this->processDetailsTable($typeId, $scanId, $flatData);

        // Get doc type key
        $docType = DB::table('document_types')->where('id', $typeId)->value('key');

        // Update scan_file
        DB::table('scan_file')
            ->where('Scan_Id', $scanId)
            ->update([
                'extract_status'  => 'Y',
                'is_extract'      => 'Y',
                'Doc_Type'        => $docType,
                'classified_by'   => $classifiedBy,
                'classified_date' => $classifiedDate,
                'DocType_Id'      => $typeId,
            ]);

        // Move data to punchfile
        $this->moveDataToPunchfile($scanId, $typeId);

        return true;
    }

    /**
     * Process the details (line items) table.
     */
    private function processDetailsTable(int $typeId, int $scanId, array $flatData): void
    {
        $detailsTable = "ext_tempdata_{$typeId}_details";

        if (!Schema::hasTable($detailsTable)) return;

        DB::table($detailsTable)->where('scan_id', $scanId)->delete();

        $detailsColumns = DB::getSchemaBuilder()->getColumnListing($detailsTable);

        foreach ($flatData as $sectionName => $sectionItems) {
            if (!is_array($sectionItems) || !isset($sectionItems[0]) || !is_array($sectionItems[0])) {
                continue;
            }

            // Separate tax rows from main items
            $mainItems = [];
            $taxData = ['gst' => null, 'sgst' => null, 'igst' => null, 'cess' => null, 'tax_amount' => 0];

            foreach ($sectionItems as $item) {
                if (!is_array($item)) continue;

                $particular = $item['Particular'] ?? '';
                if (stripos($particular, 'CGST') !== false || stripos($particular, 'SGST') !== false || stripos($particular, 'IGST') !== false) {
                    if (stripos($particular, 'CGST') !== false && isset($item['GST %'])) {
                        $taxData['gst'] = $item['GST %'];
                        $taxData['tax_amount'] += (float) ($item['Amount'] ?? 0);
                    } elseif (stripos($particular, 'SGST') !== false && isset($item['GST %'])) {
                        $taxData['sgst'] = $item['GST %'];
                        $taxData['tax_amount'] += (float) ($item['Amount'] ?? 0);
                    } elseif (stripos($particular, 'IGST') !== false && isset($item['GST %'])) {
                        $taxData['igst'] = $item['GST %'];
                        $taxData['tax_amount'] += (float) ($item['Amount'] ?? 0);
                    }
                    if (isset($item['Cess %'])) $taxData['cess'] = $item['Cess %'];
                } else {
                    $mainItems[] = $item;
                }
            }

            foreach ($mainItems as $item) {
                $detailsData = ['scan_id' => $scanId];

                foreach ($item as $key => $value) {
                    if (is_array($value)) continue;
                    $column = strtolower(str_replace([' ', '/', '-', '%'], '_', $key));
                    $matchedColumn = $this->getBestMatch($column, $detailsColumns);
                    if ($matchedColumn) {
                        if (in_array($matchedColumn, ['qty', 'mrp', 'discount_in_mrp', 'price', 'amount', 'gst', 'sgst', 'igst', 'cess', 'total_amount']) && !empty($value)) {
                            $value = (float) preg_replace('/[₹,]/', '', $value);
                        }
                        $detailsData[$matchedColumn] = $value;
                    }
                }

                // Apply tax data
                if ($taxData['gst'] !== null) $detailsData['gst'] = (float) $taxData['gst'];
                if ($taxData['sgst'] !== null) $detailsData['sgst'] = (float) $taxData['sgst'];
                if ($taxData['igst'] !== null) $detailsData['igst'] = (float) $taxData['igst'];
                if ($taxData['cess'] !== null) $detailsData['cess'] = (float) $taxData['cess'];

                if (isset($detailsData['amount']) && $taxData['tax_amount'] > 0) {
                    $detailsData['total_amount'] = (float) $detailsData['amount'] + $taxData['tax_amount'];
                } elseif (isset($detailsData['amount'])) {
                    $detailsData['total_amount'] = (float) $detailsData['amount'];
                }

                // Filter to only valid columns
                $detailsData = array_intersect_key($detailsData, array_flip($detailsColumns));
                if (!empty($detailsData)) {
                    DB::table($detailsTable)->insert($detailsData);
                }
            }
        }
    }

    /**
     * Move extracted data from temp tables to punchfile tables using field mappings.
     */
    public function moveDataToPunchfile(int $scanId, int $typeId): array
    {
        $docType = DB::table('document_types')->where('id', $typeId)->value('key');
        $tableName = 'ext_tempdata_' . $typeId;

        if (!Schema::hasTable($tableName)) {
            return ['status' => 'error', 'message' => "Temp table ({$tableName}) does not exist."];
        }

        // Get field mappings for header
        $mappings = DB::table('ext_field_mappings')
            ->where('DocType_Id', $typeId)
            ->where('has_Items_feild', 'N')
            ->select(['temp_column', 'input_type', 'select_table', 'relation_column', 'relation_value', 'punch_column', 'punch_table', 'add_condition'])
            ->get()
            ->toArray();

        if (empty($mappings)) {
            return ['status' => 'error', 'message' => 'No field mappings found.'];
        }

        $punchTable = $mappings[0]->punch_table;
        $fieldMap = collect($mappings)->keyBy('temp_column');

        // Get temp data
        $tempData = DB::table($tableName)->where('scan_id', $scanId)->first();
        if (!$tempData) {
            return ['status' => 'error', 'message' => "No data found in temp table for scan_id: {$scanId}"];
        }

        $tempData = (array) $tempData;
        $punchData = [
            'DocType'    => $docType,
            'DocTypeId'  => $typeId,
            'created_by' => auth()->id() ?? 0,
            'created_at' => now()->toDateTimeString(),
        ];

        foreach ($tempData as $key => $value) {
            if ($fieldMap->has($key)) {
                $map = $fieldMap->get($key);
                if ($map->input_type === 'select') {
                    $relatedValue = $this->getClosestValueMatch($map->select_table, $map->relation_column, $value, $map->relation_value, $map->add_condition);
                    if ($relatedValue !== null) {
                        $punchData[$map->punch_column] = $relatedValue;
                    }
                } else {
                    $punchData[$map->punch_column] = $value;
                }
            }
        }

        // Insert or update punchfile
        $existing = DB::table($punchTable)->where('scan_id', $scanId)->first();
        if ($existing) {
            DB::table($punchTable)->where('scan_id', $scanId)->update($punchData);
            $fileID = $existing->FileID ?? $existing->id ?? null;
        } else {
            $fileID = DB::table($punchTable)->insertGetId($punchData);
        }

        // Insert sub_punchfile
        if ($fileID) {
            DB::table('sub_punchfile')->insert([
                'FileID'  => $fileID,
                'Amount'  => '-' . ($punchData['Total_Amount'] ?? 0),
                'Comment' => $punchData['Remark'] ?? '',
            ]);
        }

        // Process details mappings
        $this->moveDetailsToPunchfile($scanId, $typeId);

        return ['status' => 'success', 'message' => 'Data moved successfully.'];
    }

    /**
     * Move line item details to punch tables.
     */
    private function moveDetailsToPunchfile(int $scanId, int $typeId): void
    {
        $detailsTable = "ext_tempdata_{$typeId}_details";
        if (!Schema::hasTable($detailsTable)) return;

        $detailsData = DB::table($detailsTable)->where('scan_id', $scanId)->get()->toArray();
        if (empty($detailsData)) return;

        $detailsMappings = DB::table('ext_field_mappings')
            ->where('DocType_Id', $typeId)
            ->where('has_Items_feild', 'Y')
            ->whereNotIn('punch_table', ['punchfile', 'punchfile2'])
            ->select(['temp_column', 'input_type', 'select_table', 'relation_column', 'relation_value', 'punch_column', 'punch_table', 'add_condition'])
            ->get()
            ->toArray();

        if (empty($detailsMappings)) return;

        // Delete existing detail records
        $punchTables = array_unique(array_column($detailsMappings, 'punch_table'));
        foreach ($punchTables as $pt) {
            DB::table($pt)->where('scan_id', $scanId)->delete();
        }

        $detailFieldMap = collect($detailsMappings)->keyBy('temp_column');

        foreach ($detailsData as $detail) {
            $detail = (array) $detail;
            $punchDetailData = [];
            $targetTable = null;

            foreach ($detail as $key => $value) {
                if ($detailFieldMap->has($key)) {
                    $map = $detailFieldMap->get($key);
                    $targetTable = $map->punch_table;

                    if ($map->input_type === 'select') {
                        $relatedValue = $this->getClosestValueMatch($map->select_table, $map->relation_column, $value, $map->relation_value, $map->add_condition);
                        if ($relatedValue !== null) {
                            $punchDetailData[$map->punch_column] = $relatedValue;
                        }
                    } else {
                        $punchDetailData[$map->punch_column] = $value;
                    }
                }
            }

            if (!empty($punchDetailData) && $targetTable) {
                DB::table($targetTable)->insert($punchDetailData);
            }
        }
    }

    /**
     * Update queue item status.
     */
    public function updateQueueStatus(int $queueId, string $status, ?string $result = null): void
    {
        $data = ['status' => $status, 'updated_at' => now()];
        DB::table('tbl_queues')->where('id', $queueId)->update($data);
    }

    /**
     * Log queue processing result.
     */
    public function logQueueProcess(array $data): void
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('queue_process_logs')->insert($data);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function flattenArray(array $data): array
    {
        $flat = [];
        foreach ($data as $key => $value) {
            if (is_array($value) && !isset($value[0])) {
                // Associative sub-array — flatten one level
                foreach ($value as $subKey => $subValue) {
                    $flat[$subKey] = $subValue;
                }
            } else {
                $flat[$key] = $value;
            }
        }
        return $flat;
    }

    private function getBestMatch(string $inputColumn, array $columns): ?string
    {
        $bestMatch = null;
        $bestScore = 0;
        $minDistance = PHP_INT_MAX;

        foreach ($columns as $column) {
            $normalizedInput = strtolower(preg_replace('/[^a-z0-9]/', '', $inputColumn));
            $normalizedColumn = strtolower(preg_replace('/[^a-z0-9]/', '', $column));

            similar_text($normalizedInput, $normalizedColumn, $similarity);
            $distance = levenshtein($normalizedInput, $normalizedColumn);

            if ($similarity > $bestScore || ($similarity == $bestScore && $distance < $minDistance)) {
                $bestScore = $similarity;
                $minDistance = $distance;
                $bestMatch = $column;
            }
        }

        return ($bestScore >= 70 || $minDistance <= 3) ? $bestMatch : null;
    }

    private function isValidDate(string $date): bool
    {
        if (empty($date)) return false;
        $timestamp = strtotime($date);
        return $timestamp !== false;
    }

    private function getClosestValueMatch(?string $table, ?string $searchColumn, $searchValue, ?string $returnColumn, ?string $addCondition)
    {
        if (empty($table) || empty($searchColumn) || empty($returnColumn) || empty($searchValue)) {
            return null;
        }

        $searchValue = trim((string) $searchValue);
        if (strlen($searchValue) === 0) return null;

        $searchValueCleaned = preg_replace('/\s*\([^)]+\)/', '', $searchValue);
        $searchParts = array_map('trim', preg_split('/and|&/', $searchValueCleaned, -1, PREG_SPLIT_NO_EMPTY));
        if (empty($searchParts)) return null;

        $query = DB::table($table)->select([$searchColumn, $returnColumn]);
        if (!empty($addCondition)) {
            $query->whereRaw($addCondition);
        }
        $results = $query->get();

        if ($results->isEmpty()) {
            if ($table === 'master_item') {
                return $this->insertNewItem($searchValueCleaned);
            }
            return null;
        }

        $matches = [];
        $highestOverall = 0;
        $threshold = 50;

        foreach ($results as $row) {
            $dbValue = trim((string) $row->$searchColumn);
            if (empty($dbValue)) continue;

            $highestPercent = 0;
            foreach ($searchParts as $part) {
                if (empty($part)) continue;
                $percent = 0;
                similar_text(strtoupper($part), strtoupper($dbValue), $percent);
                $percent = round($percent, 2);
                if ($percent < $threshold) $percent = 0;
                $highestPercent = max($highestPercent, $percent);
            }

            if ($highestPercent > 0) {
                $matches[] = ['percent' => $highestPercent, 'return_value' => $row->$returnColumn];
            }
            $highestOverall = max($highestOverall, $highestPercent);
        }

        if ($table === 'master_item' && ($highestOverall < $threshold || empty($matches))) {
            return $this->insertNewItem($searchValueCleaned);
        }

        if (empty($matches)) return null;

        usort($matches, fn($a, $b) => $b['percent'] <=> $a['percent']);
        return $matches[0]['return_value'];
    }

    private function insertNewItem(string $itemName): ?string
    {
        $itemName = trim($itemName);
        if (empty($itemName)) return null;

        $id = DB::table('master_item')->insertGetId([
            'item_name' => $itemName,
            'item_code' => 0,
        ]);

        if (!$id) return null;

        $itemCode = sprintf('ITEM-%03d', $id);
        DB::table('master_item')->where('item_id', $id)->update(['item_code' => $itemCode]);

        return $itemCode;
    }
}
