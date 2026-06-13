<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class PageFieldController extends Controller
{
    public function index(Page $page)
    {
        abort_if($page->user_id !== Auth::id(), 403);

        $fields = $page->fields()->orderBy('sort_order')->get();

        $dbName = DB::getDatabaseName();

        $tables = DB::table('information_schema.tables')
            ->select('TABLE_NAME')
            ->where('TABLE_SCHEMA', $dbName)
            ->whereNotIn('TABLE_NAME', [
                'migrations',
                'password_reset_tokens',
                'failed_jobs',
                'jobs',
                'cache',
                'sessions'
            ])
            ->orderBy('TABLE_NAME')
            ->pluck('TABLE_NAME')
            ->toArray();

        return view('panel.master.page-fields', compact('page', 'fields', 'tables'));
    }

    public function preview(Page $page)
    {
        abort_if($page->user_id !== Auth::id(), 403);
        $fields = $page->fields()->orderBy('sort_order')->get();
        return view('panel.master.page-preview', compact('page', 'fields'));
    }

    public function store(Request $request, Page $page)
    {
        abort_if($page->user_id !== Auth::id(), 403);

        $request->validate([
            'field_name' => ['required', 'string', 'max:255'],
            'field_type' => ['required', 'in:title,content,number,decimal,email,phone,url,password,slug,date,datetime,time,date_range,select,multi_select,radio,checkbox,toggle,color,rating,currency,slider,image,file,signature,json,repeater,formula,tax_group,summary,divider'],
        ]);

        if ($page->fields()->where('field_name', $request->field_name)->exists()) {
            return back()
                ->withErrors(['field_name_' . $request->field_type => 'Field name already exists on this page.'])
                ->withInput();
        }

        $page->fields()->create([
            'field_name' => $request->field_name,
            'field_key' => \Illuminate\Support\Str::snake(preg_replace('/[^a-zA-Z0-9\s]/', '', $request->field_name)),
            'field_type' => $request->field_type,
            'sort_order' => $page->fields()->count(),
            // Repeater starts with one default column
            'repeater_columns' => $request->field_type === 'repeater' ? [
                ['key' => 'item', 'label' => 'Item', 'type' => 'text', 'required' => false, 'default' => ''],
            ] : null,
        ]);

        return back()->with('success', 'Field added successfully.');
    }

    public function updateSettings(Request $request, Page $page, PageField $field)
    {
        abort_if($page->user_id !== Auth::id(), 403);

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'column_name' => ['nullable', 'string', 'max:255'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'default_value' => ['nullable', 'string', 'max:255'],
            'col_span' => ['nullable', 'integer', 'min:1', 'max:3'],
            'is_required' => ['nullable', 'boolean'],
            'is_unique' => ['nullable', 'boolean'],
            'is_nullable' => ['nullable', 'boolean'],
            'column_length' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $data['is_required'] = $request->boolean('is_required');
        $data['is_unique'] = $request->boolean('is_unique');
        $data['is_nullable'] = $request->boolean('is_nullable');

        if ($request->filled('options_json')) {
            $data['options'] = json_decode($request->options_json, true);
        }

        // Handle date/datetime/time field options
        if (in_array($field->field_type, ['date', 'datetime', 'time'])) {
            $options = $field->options ?? [];
            $options['use_current_date'] = $request->boolean('use_current_date');
            $data['options'] = $options;
        }

        // Handle formula field
        if ($request->filled('formula_json')) {
            $data['formula'] = json_decode($request->formula_json, true);
        }

        // Handle visibility rules
        if ($request->filled('visibility_rules_json')) {
            $data['visibility_rules'] = json_decode($request->visibility_rules_json, true);
        }

        // Handle validation rules
        if ($request->filled('validation_rules_json')) {
            $data['validation_rules'] = json_decode($request->validation_rules_json, true);
        }

        // Handle auto-fill config
        if ($request->filled('auto_fill_json')) {
            $data['auto_fill'] = json_decode($request->auto_fill_json, true);
        }

        // Handle summary config
        if ($request->filled('summary_config_json')) {
            $data['summary_config'] = json_decode($request->summary_config_json, true);
        }

        // Handle tax config
        if ($request->filled('tax_config_json')) {
            $data['tax_config'] = json_decode($request->tax_config_json, true);
        }

        // Handle field_key update
        if ($request->filled('field_key')) {
            $data['field_key'] = $request->field_key;
        }

        $field->update($data);

        return back()->with('success', 'Field settings saved.');
    }

    public function updateRepeaterColumns(Request $request, Page $page, PageField $field)
    {
        abort_if($page->user_id !== Auth::id(), 403);
        abort_if($field->field_type !== 'repeater', 422);

        $request->validate([
            'columns' => ['required', 'array', 'min:1'],
            'columns.*.key' => ['required', 'string', 'max:64', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'columns.*.label' => ['required', 'string', 'max:255'],
            'columns.*.type' => ['required', 'in:text,number,decimal,email,date,datetime,time,select,textarea,checkbox,formula'],
            'columns.*.required' => ['nullable', 'boolean'],
            'columns.*.default' => ['nullable', 'string', 'max:255'],
        ]);

        // Ensure keys are unique within the repeater
        $keys = array_column($request->columns, 'key');
        if (count($keys) !== count(array_unique($keys))) {
            return back()->withErrors(['columns' => 'Column keys must be unique.']);
        }

        $columns = array_map(fn($c) => [
            'key' => $c['key'],
            'label' => $c['label'],
            'type' => $c['type'],
            'required' => !empty($c['required']),
            'default' => $c['default'] ?? '',
            'formula' => $c['formula'] ?? '',
            'show_summary' => !empty($c['show_summary']),
            'options' => !empty($c['options']) ? (is_array($c['options']) ? $c['options'] : json_decode($c['options'], true)) : [],
            'dynamic' => !empty($c['dynamic']) ? (is_array($c['dynamic']) ? $c['dynamic'] : json_decode($c['dynamic'], true)) : null,
            'auto_fill_enabled' => !empty($c['auto_fill_enabled']),
            'auto_fill_mappings' => !empty($c['auto_fill_mappings']) ? (is_array($c['auto_fill_mappings']) ? $c['auto_fill_mappings'] : json_decode($c['auto_fill_mappings'], true)) : [],
        ], $request->columns);

        $field->update(['repeater_columns' => $columns]);

        return back()->with('success', 'Repeater columns saved.');
    }

    public function destroy(Page $page, PageField $field)
    {
        abort_if($page->user_id !== Auth::id(), 403);
        $field->delete();
        return back()->with('success', 'Field removed.');
    }

    public function reorder(Request $request, Page $page)
    {
        abort_if($page->user_id !== Auth::id(), 403);

        $request->validate(['order' => ['required', 'array']]);

        foreach ($request->order as $position => $fieldId) {
            $page->fields()->where('id', $fieldId)->update(['sort_order' => $position]);
        }

        return response()->json(['ok' => true]);
    }

    public function getColumns(Request $request)
    {
        $table = $request->query('table');
        if (!$table || !Schema::hasTable($table)) {
            return response()->json([]);
        }
        return response()->json(Schema::getColumnListing($table));
    }

    /**
     * Lookup endpoint: fetch a row from a table by ID and return specified columns.
     * Used for auto-fill when a dropdown selection changes.
     * GET /master/page-builder/lookup?table=vendors&id=5&columns=address,gstin,city
     */
    public function lookup(Request $request)
    {
        $table = $request->query('table');
        $id = $request->query('id');
        $columns = $request->query('columns', '*');

        if (!$table || !$id || !Schema::hasTable($table)) {
            return response()->json([]);
        }

        $selectCols = $columns === '*' ? ['*'] : explode(',', $columns);
        $record = DB::table($table)->where('id', $id)->first($selectCols);

        if (!$record) {
            return response()->json([]);
        }

        return response()->json((array) $record);
    }

    /**
     * Server-side search for select fields (Select2 / server search mode).
     * GET /master/page-builder/search-options?table=vendors&search=abc&label_col=name&value_col=id&search_cols=name,code&limit=20
     */
    public function searchOptions(Request $request)
    {
        $table = $request->query('table');
        $search = $request->query('search', '');
        $labelCol = $request->query('label_col', 'name');
        $valueCol = $request->query('value_col', 'id');
        $searchCols = $request->query('search_cols', $labelCol);
        $limit = (int) $request->query('limit', 20);

        if (!$table || !Schema::hasTable($table)) {
            return response()->json([]);
        }

        $query = DB::table($table)->select($valueCol, $labelCol);

        if ($search) {
            $cols = array_map('trim', explode(',', $searchCols));
            $query->where(function ($q) use ($cols, $search) {
                foreach ($cols as $col) {
                    $q->orWhere($col, 'LIKE', "%{$search}%");
                }
            });
        }

        $results = $query->limit($limit)->get()->map(function ($row) use ($valueCol, $labelCol) {
            return ['id' => $row->$valueCol, 'text' => $row->$labelCol];
        });

        return response()->json($results);
    }
}
