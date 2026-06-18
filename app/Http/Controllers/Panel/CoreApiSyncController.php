<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\CoreApiList;
use App\Models\CoreApiSyncLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;

class CoreApiSyncController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Prefix helper — all local dynamic tables go through this
    // ─────────────────────────────────────────────────────────────────────────

    private function localTableName(string $rawName): string
    {
        $prefix = config('core-api.table_prefix', 'core_');

        // Sanitise: replace slashes/hyphens/spaces with underscores, strip
        // anything that isn't a valid table name character
        $clean = strtolower(trim($rawName));
        $clean = preg_replace('/[\s\-\/]+/', '_', $clean);
        $clean = preg_replace('/[^a-z0-9_]/', '', $clean);
        $clean = trim($clean, '_');

        if ($clean === '') {
            return $prefix . 'unknown';
        }

        // Avoid double-prefixing
        if (str_starts_with($clean, $prefix)) {
            return $clean;
        }

        return $prefix . $clean;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DataTables server-side data for the API list
    // ─────────────────────────────────────────────────────────────────────────

    public function data(Request $request)
    {
        $query = CoreApiList::query();

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('api_end_point', 'like', "%{$search}%")
                  ->orWhere('table_name',   'like', "%{$search}%")
                  ->orWhere('description',  'like', "%{$search}%");
            });
        }

        $total    = CoreApiList::count();
        $filtered = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 25);
        $order    = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $cols     = ['id', 'api_end_point', 'table_name', 'last_synced_at', 'sync_status'];
        $col      = $cols[(int) ($order[0]['column'] ?? 0)] ?? 'id';
        $dir      = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $rows = $query->orderBy($col, $dir)->skip($start)->take($length)->get();

        // Fetch last-used params from sync logs in one query
        $lastLogs = CoreApiSyncLog::whereIn('api_end_point', $rows->pluck('api_end_point'))
            ->orderByDesc('id')
            ->get(['api_end_point', 'params_used'])
            ->unique('api_end_point')
            ->keyBy('api_end_point');

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($r) => [
                'id'               => $r->id,
                'api_end_point'    => $r->api_end_point,
                'table_name'       => $r->table_name,
                'description'      => $r->description,
                'sync_status'      => $r->sync_status ?? 'pending',
                'last_synced_at'   => $r->last_synced_at?->format('d M Y H:i') ?? '—',
                // stored parameter keys (blank values — user fills these in)
                'parameters'       => $r->parameters ? (json_decode($r->parameters, true) ?: []) : [],
                // last actually-used values from the most recent sync log
                'last_used_params' => isset($lastLogs[$r->api_end_point]) && $lastLogs[$r->api_end_point]->params_used
                    ? (json_decode($lastLogs[$r->api_end_point]->params_used, true) ?: [])
                    : [],
            ]),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Show page
    // ─────────────────────────────────────────────────────────────────────────

    public function index()
    {
        return view('panel.settings.core-api-sync');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Fetch API list from core server
    // ─────────────────────────────────────────────────────────────────────────

    public function fetchApiList()
    {
        $baseUrl = rtrim(config('core-api.base_url'), '/');
        $apiKey  = config('core-api.api_key');

        $response = Http::withHeaders(['api-key' => $apiKey])
            ->get("{$baseUrl}/project/apis");

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reach core server. Status: ' . $response->status(),
            ], 502);
        }

        $apiList = $response->json('api_list', []);

        if (empty($apiList)) {
            return response()->json([
                'success' => false,
                'message' => 'Core server returned an empty api_list.',
            ], 422);
        }

        foreach ($apiList as $item) {
            $remoteId   = (int) ($item['id'] ?? 0);
            $localTable = $this->localTableName($item['table_name'] ?? ($item['api_end_point'] ?? ''));

            // Collect every field from the remote item except 'id' (stored as remote_id)
            $extraFields = collect($item)
                ->except(['id'])
                ->mapWithKeys(fn($v, $k) => [$k => $v])
                ->toArray();

            // Ensure core_api_list has a column for every new field the remote API sends
            $existingCols = Schema::getColumnListing('core_api_list');
            foreach ($extraFields as $fieldKey => $fieldVal) {
                if (! in_array($fieldKey, $existingCols, true)) {
                    Schema::table('core_api_list', function (Blueprint $table) use ($fieldKey) {
                        $table->text($fieldKey)->nullable();
                    });
                }
            }

            // Normalise 'parameters': if the remote sends it as an array/object, JSON-encode it;
            // if it's a plain string (e.g. "state_id"), treat it as a single required param key
            // and store {"state_id": ""} so the modal can pre-fill it correctly.
            // If absent leave null.
            if (array_key_exists('parameters', $extraFields)) {
                $raw = $extraFields['parameters'];
                if (is_array($raw)) {
                    // Already an associative object from the API
                    $extraFields['parameters'] = json_encode($raw);
                } elseif (is_string($raw) && $raw !== '') {
                    // Plain string = the name(s) of required param key(s).
                    // Strip any surrounding quotes the remote API may include (e.g. "state_id")
                    // Could be comma-separated: "state_id,type" or "\"state_id\""
                    $clean = trim($raw, '"\'\ ');
                    $keys  = array_filter(array_map(fn($k) => trim($k, '"\'\ '), explode(',', $clean)));
                    $paramObj = [];
                    foreach ($keys as $k) {
                        if ($k !== '') $paramObj[$k] = '';
                    }
                    $extraFields['parameters'] = $paramObj ? json_encode($paramObj) : null;
                } else {
                    $extraFields['parameters'] = null;
                }
            }

            $existing = CoreApiList::where('remote_id', $remoteId)->first();

            if ($existing) {
                // Update all fields EXCEPT table_name (preserve the locally-stored prefixed name)
                $updateData = $extraFields;
                unset($updateData['table_name']);
                $existing->update($updateData);
            } else {
                CoreApiList::create(array_merge(
                    ['remote_id' => $remoteId, 'table_name' => $localTable],
                    $extraFields
                ));
            }
        }

        $upserted = count($apiList);

        return response()->json([
            'success' => true,
            'message' => "Fetched {$upserted} APIs from core server.",
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sync a single API endpoint into a local table
    // ─────────────────────────────────────────────────────────────────────────

    public function sync(Request $request)
    {
        $request->validate([
            'api_end_point' => ['required', 'string', 'max:255'],
            'table_name'    => ['nullable', 'string', 'max:255'],
        ]);

        $apiEndPoint = $request->input('api_end_point');

        // Fall back to endpoint name if table_name is missing or blank
        $rawTable  = $request->filled('table_name')
            ? $request->input('table_name')
            : $apiEndPoint;

        $tableName = $this->localTableName($rawTable);
        $baseUrl   = rtrim(config('core-api.base_url'), '/');
        $apiKey    = config('core-api.api_key');
        $startedAt = Carbon::now();

        $coreApi = CoreApiList::where('api_end_point', $apiEndPoint)->first();

        // Build query params: start from stored parameters, then let UI-supplied
        // values override them (UI wins so the user can adjust per-sync).
        $storedParams = [];
        if ($coreApi && ! empty($coreApi->parameters)) {
            $decoded = json_decode($coreApi->parameters, true);
            $storedParams = is_array($decoded) ? $decoded : [];
        }

        $uiParams = $request->except(['api_end_point', 'table_name', '_token']);

        // Merge: stored params provide the keys, UI-supplied values fill them in.
        // Stored values that are blank ('') are treated as placeholder keys only —
        // the actual value must come from the UI. UI always wins on any key.
        $extraParams = array_merge($storedParams, $uiParams);

        // Remove any remaining empty-string values that the UI did not fill in
        // (they would cause the external API to receive key= with no value)
        $extraParams = array_filter($extraParams, fn($v) => $v !== '' && $v !== null);

        $response = Http::withHeaders(['api-key' => $apiKey])
            ->get("{$baseUrl}/{$apiEndPoint}", $extraParams);

        if ($response->failed()) {
            $this->saveLog($coreApi, $apiEndPoint, $tableName, 0, 0, 0, 'failed',
                'Core server error. Status: ' . $response->status(), $startedAt, $extraParams);

            if ($coreApi) {
                $coreApi->update(['sync_status' => 'failed']);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to reach core server. Status: ' . $response->status(),
            ], 502);
        }

        $list = $response->json('list', []);

        if (empty($list)) {
            $this->saveLog($coreApi, $apiEndPoint, $tableName, 0, 0, 0, 'success',
                'Core server returned an empty list.', $startedAt, $extraParams);

            if ($coreApi) {
                $coreApi->update(['sync_status' => 'synced', 'last_synced_at' => Carbon::now()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sync complete — remote list is empty.',
                'added' => 0, 'updated' => 0, 'removed' => 0,
            ]);
        }

        // Create table if it doesn't exist yet
        $this->ensureTable($tableName, $list[0]);

        // Add any new columns that appear in the response but not yet in the table
        $existingColumns = Schema::getColumnListing($tableName);
        foreach ($list[0] as $col => $val) {
            $localCol = ($col === 'id') ? 'remote_id' : $col;
            if (! in_array($localCol, $existingColumns, true)) {
                Schema::table($tableName, function (Blueprint $table) use ($localCol) {
                    $table->text($localCol)->nullable()->after('remote_id');
                });
            }
        }

        $remoteIds = [];
        $added     = 0;
        $updated   = 0;

        foreach ($list as $row) {
            $remoteId = (int) ($row['id'] ?? 0);
            if ($remoteId === 0) continue;

            $remoteIds[] = $remoteId;

            $payload = ['remote_id' => $remoteId];
            foreach ($row as $col => $val) {
                if ($col === 'id') continue;
                $payload[$col] = $val;
            }

            if (DB::table($tableName)->where('remote_id', $remoteId)->exists()) {
                DB::table($tableName)->where('remote_id', $remoteId)->update($payload);
                $updated++;
            } else {
                DB::table($tableName)->insert($payload);
                $added++;
            }
        }

        $removed = DB::table($tableName)->whereNotIn('remote_id', $remoteIds)->count();
        DB::table($tableName)->whereNotIn('remote_id', $remoteIds)->delete();

        if ($coreApi) {
            $coreApi->update([
                'sync_status'    => 'synced',
                'last_synced_at' => Carbon::now(),
            ]);
        }

        $this->saveLog($coreApi, $apiEndPoint, $tableName, $added, $updated, $removed,
            'success', null, $startedAt, $extraParams);

        return response()->json([
            'success' => true,
            'message' => "Sync complete for [{$apiEndPoint}].",
            'added'   => $added,
            'updated' => $updated,
            'removed' => $removed,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DataTables server-side data for the View Data modal (dynamic table)
    // ─────────────────────────────────────────────────────────────────────────

    public function modalData(Request $request)
    {
        $request->validate([
            'table_name' => ['required', 'string', 'max:255'],
        ]);

        $tableName = $this->localTableName($request->input('table_name'));

        if (! Schema::hasTable($tableName)) {
            return response()->json([
                'draw'            => (int) $request->input('draw', 1),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'columns'         => [],
                'error'           => "Table [{$tableName}] does not exist. Sync first.",
            ]);
        }

        $columns = Schema::getColumnListing($tableName);
        $query   = DB::table($tableName);
        $total   = (clone $query)->count();

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(function ($q) use ($columns, $search) {
                foreach ($columns as $col) {
                    $q->orWhere($col, 'like', "%{$search}%");
                }
            });
        }

        $filtered = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 25);
        $order    = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $colIndex = (int) ($order[0]['column'] ?? 0);
        $sortCol  = $columns[$colIndex] ?? ($columns[0] ?? 'id');
        $dir      = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $rows = $query->orderBy($sortCol, $dir)->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'columns'         => $columns,
            'data'            => $rows->map(fn($row) => (array) $row),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Truncate a dynamic local table
    // ─────────────────────────────────────────────────────────────────────────

    public function emptyTable(Request $request)
    {
        $request->validate([
            'table_name' => ['required', 'string', 'max:255'],
        ]);

        $tableName = $this->localTableName($request->input('table_name'));

        if (! Schema::hasTable($tableName)) {
            return response()->json([
                'success' => false,
                'message' => "Table [{$tableName}] does not exist.",
            ]);
        }

        DB::table($tableName)->truncate();

        CoreApiList::where('table_name', $tableName)
            ->update(['sync_status' => 'pending', 'last_synced_at' => null]);

        return response()->json([
            'success' => true,
            'message' => "Table [{$tableName}] cleared.",
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Drop a dynamic local table and remove its API list entry
    // ─────────────────────────────────────────────────────────────────────────

    public function dropTable(Request $request)
    {
        $request->validate([
            'table_name' => ['required', 'string', 'max:255'],
        ]);

        $tableName = $this->localTableName($request->input('table_name'));

        Schema::dropIfExists($tableName);

        CoreApiList::where('table_name', $tableName)->delete();

        return response()->json([
            'success' => true,
            'message' => "Table [{$tableName}] dropped and API entry removed.",
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function ensureTable(string $tableName, array $sampleRow): void
    {
        if (Schema::hasTable($tableName)) return;

        Schema::create($tableName, function (Blueprint $table) use ($sampleRow) {
            $table->increments('id');
            $table->unsignedInteger('remote_id')->unique();

            foreach ($sampleRow as $col => $val) {
                if ($col === 'id') continue;
                $table->text($col)->nullable();
            }
        });
    }

    private function saveLog(
        ?CoreApiList $coreApi,
        string $apiEndPoint,
        string $tableName,
        int $added,
        int $updated,
        int $removed,
        string $status,
        ?string $message,
        Carbon $startedAt,
        array $paramsUsed = []
    ): void {
        CoreApiSyncLog::create([
            'core_api_list_id' => $coreApi?->id,
            'api_end_point'    => $apiEndPoint,
            'params_used'      => $paramsUsed ? json_encode($paramsUsed) : null,
            'table_name'       => $tableName,
            'added'            => $added,
            'updated'          => $updated,
            'removed'          => $removed,
            'status'           => $status,
            'message'          => $message,
            'started_at'       => $startedAt,
            'ended_at'         => Carbon::now(),
        ]);
    }
}
