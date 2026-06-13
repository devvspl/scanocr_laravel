<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GeneratorController extends Controller
{
    public function generate(Page $page)
    {
        abort_if($page->user_id !== Auth::id(), 403);
        $fields = $page->fields;
        if ($fields->isEmpty()) {
            return back()->with('error', 'Add at least one field before generating.');
        }
        $modelName = Str::studly(Str::singular($page->page_name));
        $tableName = 'gen_' . Str::snake(Str::plural($page->page_name));
        $routeSlug = Str::slug(Str::plural($page->page_name));
        $routeBase = 'generated.' . $routeSlug;
        $viewFolder = 'generated/' . $routeSlug;
        try {
            $this->createMigration($tableName, $fields);
            $this->createModel($modelName, $tableName, $fields);
            $this->createExport($modelName, $fields);
            $this->createController($modelName, $routeBase, $routeSlug, $viewFolder, $fields, $tableName);
            $this->createViews($modelName, $routeBase, $viewFolder, $fields);
            $this->appendRoutes($modelName, $routeSlug);
            Artisan::call('migrate', ['--force' => true]);
            $page->update(['is_generated' => true]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Generation failed: ' . $e->getMessage());
        }
        return back()->with('success', "'{$page->page_name}' generated. Visit /generated/{$routeSlug} to use it.");
    }

    // ── Migration ──────────────────────────────────────────────────────────────

    private function colName($field): string
    {
        if ($field->column_name) {
            return preg_replace('/[^a-z0-9_]/', '', strtolower($field->column_name));
        }
        return Str::snake(preg_replace('/[^a-zA-Z0-9\s]/', '', $field->field_name));
    }

    private function createMigration(string $tableName, $fields): void
    {
        $timestamp = now()->format('Y_m_d_His');

        if (!Schema::hasTable($tableName)) {
            // ── Fresh create migration ────────────────────────────────────────
            $cols = '';
            $repeaterTables = '';
            foreach ($fields as $f) {
                $col = $this->colName($f);
                if ($f->field_type === 'repeater') {
                    $subTableName = $tableName . '_' . $col;
                    $subTableCols = '';
                    $subCols = $f->repeater_columns ?? [['key' => 'item', 'label' => 'Item', 'type' => 'text']];
                    foreach ($subCols as $sc) {
                        $scKey = preg_replace('/[^a-z0-9_]/', '', strtolower($sc['key'] ?? 'item'));
                        $subTableCols .= $this->repeaterColType($sc, $scKey);
                    }
                    $mainTableId = Str::snake(Str::singular($tableName)) . '_id';
                    $repeaterTables .= "        Schema::create('{$subTableName}', function (Blueprint \$table) {\n            \$table->id();\n            \$table->foreignId('{$mainTableId}')->constrained('{$tableName}')->onDelete('cascade');\n{$subTableCols}            \$table->timestamps();\n        });\n";
                } else {
                    $cols .= $this->migrationColumn($f, $col);
                }
            }
            $stub = "<?php\nuse Illuminate\Database\Migrations\Migration;\nuse Illuminate\Database\Schema\Blueprint;\nuse Illuminate\Support\Facades\Schema;\nreturn new class extends Migration {\n    public function up(): void\n    {\n        if (!Schema::hasTable('{$tableName}')) {\n            Schema::create('{$tableName}', function (Blueprint \$table) {\n                \$table->id();\n{$cols}                \$table->timestamps();\n            });\n        }\n{$repeaterTables}    }\n    public function down(): void {\n        // Drop repeater tables first\n";
            foreach ($fields as $f) {
                if ($f->field_type === 'repeater') {
                    $col = $this->colName($f);
                    $stub .= "        Schema::dropIfExists('{$tableName}_{$col}');\n";
                }
            }
            $stub .= "        Schema::dropIfExists('{$tableName}');\n    }\n};\n";
            file_put_contents(database_path("migrations/{$timestamp}_create_{$tableName}_table.php"), $stub);
            return;
        }

        // ── Alter migration for Re-Generate ──────────────────────────────────
        $existingCols = array_map(
            fn($c) => $c->Field,
            DB::select("SHOW COLUMNS FROM `{$tableName}`")
        );
        // Reserved columns we never touch
        $reserved = ['id', 'created_at', 'updated_at'];

        $fieldCols = $fields->where('field_type', '!=', 'repeater')->map(fn($f) => $this->colName($f))->toArray();

        // Columns to ADD (in fields but not in table)
        $toAdd = [];
        $toDrop = [];
        foreach ($fields as $f) {
            $col = $this->colName($f);
            if (!in_array($col, $existingCols)) {
                $toAdd[] = ['field' => $f, 'col' => $col];
            }
        }

        foreach ($existingCols as $col) {
            if (in_array($col, $reserved))
                continue;

            // If the column name is now used for a repeater, it MUST be dropped from the main table
            // because it will be handled by a separate table/relationship.
            $isRepeater = $fields->contains(fn($f) => $this->colName($f) === $col && $f->field_type === 'repeater');

            if ($isRepeater || !in_array($col, $fieldCols)) {
                // If it's a repeater, we force drop it to avoid shadowing the relationship.
                // Otherwise, we only drop if it's empty.
                if ($isRepeater) {
                    $toDrop[] = $col;
                } else {
                    $hasData = DB::table($tableName)->whereNotNull($col)->where($col, '!=', '')->exists();
                    if (!$hasData) {
                        $toDrop[] = $col;
                    }
                }
            }
        }

        if (empty($toAdd) && empty($toDrop)) {
            // Check for missing repeater tables even if main table is fine
            $hasMissingRepeaters = false;
            foreach ($fields as $f) {
                if ($f->field_type === 'repeater') {
                    if (!Schema::hasTable($tableName . '_' . $this->colName($f))) {
                        $hasMissingRepeaters = true;
                        break;
                    }
                }
            }
            if (!$hasMissingRepeaters)
                return;
        }

        $addLines = '';
        $dropLines = '';
        $downAdd = '';
        $downDrop = '';
        $repeaterTables = '';

        foreach ($toAdd as $item) {
            $line = $this->migrationColumn($item['field'], $item['col']);
            if ($line) {
                $addLines .= '            ' . trim($line) . "\n";
                $downDrop .= "            \$table->dropColumn('{$item['col']}');\n";
            }
        }

        foreach ($fields as $f) {
            if ($f->field_type === 'repeater') {
                $col = $this->colName($f);
                $subTableName = $tableName . '_' . $col;
                if (!Schema::hasTable($subTableName)) {
                    $subTableCols = '';
                    $subCols = $f->repeater_columns ?? [['key' => 'item', 'label' => 'Item', 'type' => 'text']];
                    foreach ($subCols as $sc) {
                        $scKey = preg_replace('/[^a-z0-9_]/', '', strtolower($sc['key'] ?? 'item'));
                        $subTableCols .= $this->repeaterColType($sc, $scKey);
                    }
                    $mainTableId = Str::snake(Str::singular($tableName)) . '_id';
                    $repeaterTables .= "        Schema::create('{$subTableName}', function (Blueprint \$table) {\n            \$table->id();\n            \$table->foreignId('{$mainTableId}')->constrained('{$tableName}')->onDelete('cascade');\n{$subTableCols}            \$table->timestamps();\n        });\n";
                }
            }
        }

        foreach ($toDrop as $col) {
            $dropLines .= "            \$table->dropColumn('{$col}');\n";
            $downAdd .= "            \$table->string('{$col}')->nullable();\n";
        }

        $upBody = '';
        if ($addLines || $dropLines) {
            $upBody = "        Schema::table('{$tableName}', function (Blueprint \$table) {\n{$addLines}{$dropLines}        });\n";
        }
        $upBody .= $repeaterTables;

        $downBody = "        Schema::table('{$tableName}', function (Blueprint \$table) {\n{$downAdd}{$downDrop}        });\n";

        $stub = "<?php\nuse Illuminate\Database\Migrations\Migration;\nuse Illuminate\Database\Schema\Blueprint;\nuse Illuminate\Support\Facades\Schema;\nreturn new class extends Migration {\n    public function up(): void\n    {\n{$upBody}    }\n    public function down(): void\n    {\n{$downBody}    }\n};\n";
        file_put_contents(database_path("migrations/{$timestamp}_alter_{$tableName}_table.php"), $stub);
    }

    private function migrationColumn($field, string $col): string
    {
        if ($field->field_type === 'repeater')
            return '';

        $nullable = $field->is_nullable ? '->nullable()' : '';
        $unique = $field->is_unique ? '->unique()' : '';
        $default = ($field->default_value !== null && $field->default_value !== '') ? "->default('" . addslashes($field->default_value) . "')" : '';
        $len = $field->column_length ? ", {$field->column_length}" : '';
        $type = match ($field->field_type) {
            'number' => "\$table->integer('{$col}')",
            'decimal', 'currency' => "\$table->decimal('{$col}', 15, 2)",
            'toggle', 'checkbox' => "\$table->boolean('{$col}')",
            'date' => "\$table->date('{$col}')",
            'datetime' => "\$table->dateTime('{$col}')",
            'time' => "\$table->time('{$col}')",
            'rating' => "\$table->unsignedTinyInteger('{$col}')",
            'json' => "\$table->json('{$col}')",
            'content' => "\$table->text('{$col}')",
            'image', 'file' => "\$table->json('{$col}')",
            default => "\$table->string('{$col}'{$len})",
        };
        return "            {$type}{$nullable}{$unique}{$default};\n";
    }

    private function repeaterColType($subCol, string $col): string
    {
        $type = match ($subCol['type'] ?? 'text') {
            'number' => "\$table->integer('{$col}')",
            'decimal' => "\$table->decimal('{$col}', 15, 2)",
            'date' => "\$table->date('{$col}')",
            'datetime' => "\$table->dateTime('{$col}')",
            'time' => "\$table->time('{$col}')",
            'checkbox' => "\$table->boolean('{$col}')",
            'textarea' => "\$table->text('{$col}')",
            default => "\$table->string('{$col}')",
        };
        return "            {$type}->nullable();\n";
    }

    // ── Model ──────────────────────────────────────────────────────────────────

    private function createModel(string $modelName, string $tableName, $fields): void
    {
        $dir = app_path('Models/Generated');
        if (!is_dir($dir))
            mkdir($dir, 0755, true);

        // Main Model
        $fillable = $fields->where('field_type', '!=', 'repeater')->map(fn($f) => "'" . ($this->colName($f)) . "'")->implode(', ');
        $casts = '';
        $relations = '';

        foreach ($fields as $f) {
            $col = $this->colName($f);
            if ($f->field_type === 'repeater') {
                $subModelName = $modelName . Str::studly(Str::singular($col));
                $mainTableId = Str::snake(Str::singular($tableName)) . '_id';
                $relations .= "\n    public function {$col}(): \\Illuminate\\Database\\Eloquent\\Relations\\HasMany\n    {\n        return \$this->hasMany({$subModelName}::class, '{$mainTableId}');\n    }\n";
                $this->createSubModel($subModelName, $tableName . '_' . $col, $f, $tableName);
                continue;
            }

            $cast = match ($f->field_type) {
                'toggle', 'checkbox' => "'boolean'",
                'number' => "'integer'",
                'decimal', 'currency' => "'float'",
                'json', 'image', 'file' => "'array'",
                default => null,
            };
            if ($cast)
                $casts .= "        '{$col}' => {$cast},\n";
        }

        $castsBlock = $casts ? "    protected \$casts = [\n{$casts}    ];\n" : '';
        $stub = "<?php\nnamespace App\Models\Generated;\nuse Illuminate\Database\Eloquent\Model;\nclass {$modelName} extends Model\n{\n    protected \$table = '{$tableName}';\n    protected \$fillable = [{$fillable}];\n{$castsBlock}{$relations}}\n";
        file_put_contents("{$dir}/{$modelName}.php", $stub);
    }

    private function createSubModel(string $modelName, string $tableName, $field, string $parentTableName): void
    {
        $dir = app_path('Models/Generated');
        $parentId = Str::snake(Str::singular($parentTableName)) . '_id';
        $subCols = $field->repeater_columns ?? [['key' => 'item']];
        $fillable = ["'{$parentId}'"];
        foreach ($subCols as $sc) {
            $fillable[] = "'" . preg_replace('/[^a-z0-9_]/', '', strtolower($sc['key'] ?? 'item')) . "'";
        }
        $fillableStr = implode(', ', $fillable);

        $stub = "<?php\nnamespace App\Models\Generated;\nuse Illuminate\Database\Eloquent\Model;\nclass {$modelName} extends Model\n{\n    protected \$table = '{$tableName}';\n    protected \$fillable = [{$fillableStr}];\n}\n";
        file_put_contents("{$dir}/{$modelName}.php", $stub);
    }

    // ── Export ─────────────────────────────────────────────────────────────────

    private function createExport(string $modelName, $fields): void
    {
        $dir = app_path('Exports/Generated');
        if (!is_dir($dir))
            mkdir($dir, 0755, true);

        $headings = $fields->map(fn($f) => "'" . ($f->label ?: Str::headline($this->colName($f))) . "'")->implode(', ');
        $cols = $fields->map(fn($f) => "'" . ($this->colName($f)) . "'")->implode(', ');

        $stub = <<<PHP
            <?php
            namespace App\\Exports\\Generated;

            use App\\Models\\Generated\\{$modelName};
            use Maatwebsite\Excel\Concerns\FromCollection;
            use Maatwebsite\Excel\Concerns\WithHeadings;
            use Maatwebsite\Excel\Concerns\WithMapping;
            use Maatwebsite\Excel\Concerns\ShouldAutoSize;
            use Maatwebsite\Excel\Concerns\WithStyles;
            use Maatwebsite\Excel\Concerns\WithEvents;
            use Maatwebsite\Excel\Events\AfterSheet;
            use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
            use PhpOffice\PhpSpreadsheet\Style\Fill;
            use PhpOffice\PhpSpreadsheet\Style\Border;
            use PhpOffice\PhpSpreadsheet\Style\Alignment;

            class {$modelName}Export implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
            {
                protected array \$columns = [{$cols}];
                protected array \$headingLabels = [{$headings}];

                public function collection()
                {
                    return {$modelName}::all();
                }

                public function headings(): array
                {
                    return [\$this->headingLabels];
                }

                public function map(\$row): array
                {
                    return array_map(fn(\$col) => \$row->{\$col} ?? '', \$this->columns);
                }

                public function styles(Worksheet \$sheet)
                {
                    \$lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count(\$this->headingLabels));
                    \$sheet->getStyle("A1:{\$lastCol}1")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B91C1C']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    return [];
                }

                public function registerEvents(): array
                {
                    return [
                        AfterSheet::class => function (AfterSheet \$event) {
                            \$sheet     = \$event->sheet->getDelegate();
                            \$colCount  = count(\$this->headingLabels);
                            \$lastCol   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(\$colCount);
                            \$totalRows = \$sheet->getHighestRow();
                            for (\$row = 2; \$row <= \$totalRows; \$row++) {
                                \$isEven = \$row % 2 === 1;
                                \$sheet->getStyle("A{\$row}:{\$lastCol}{\$row}")->applyFromArray([
                                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => \$isEven ? 'FEF2F2' : 'FFFFFF']],
                                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FECACA']]],
                                ]);
                            }
                            \$sheet->getStyle("A1:{\$lastCol}1")->applyFromArray([
                                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '7F1D1D']]],
                            ]);
                            \$sheet->getRowDimension(1)->setRowHeight(20);
                        },
                    ];
                }
            }
            PHP;
        file_put_contents("{$dir}/{$modelName}Export.php", $stub);
    }

    // ── Controller ─────────────────────────────────────────────────────────────

    private function createController(string $modelName, string $routeBase, string $routeSlug, string $viewFolder, $fields, string $tableName): void
    {
        $dir = app_path('Http/Controllers/Generated');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        
        $varName = Str::camel($modelName);
        $varPlural = Str::camel(Str::plural($modelName));
        $hasUnique = $fields->contains('is_unique', true);
        $ruleImport = $hasUnique ? "use Illuminate\Validation\Rule;\n" : '';

        // Generate validation rules
        $storeLines = '';
        $updateLines = '';
        foreach ($fields as $f) {
            $col = $this->colName($f);
            // File/image fields are always nullable at the array level — presence is handled by file saving logic
            $isFileField = in_array($f->field_type, ['image', 'file']);
            $baseRules = ($f->is_required && !$isFileField) ? ["'required'"] : ["'nullable'"];
            $f->table_name = $tableName;
            $storeLines .= "            '{$col}' => [{$this->fieldValidationRules($f, $baseRules)}],\n";
            $updateLines .= "            '{$col}' => [{$this->fieldValidationRules($f, $baseRules, $varName . '->id')}],\n";
        }

        // Repeater saving logic
        $repeaterSaving = '';
        foreach ($fields as $f) {
            if ($f->field_type === 'repeater') {
                $col = $this->colName($f);
                $repeaterSaving .= "        if (\$request->has('{$col}')) {\n"
                    . "            \${$varName}->{$col}()->delete();\n"
                    . "            \$rows = collect(\$request->input('{$col}'))->filter(function(\$row) {\n"
                    . "                return !empty(array_filter(\$row, fn(\$v) => !is_null(\$v) && \$v !== ''));\n"
                    . "            });\n"
                    . "            if (\$rows->isNotEmpty()) \${$varName}->{$col}()->createMany(\$rows->toArray());\n"
                    . "        }\n";
            }
        }

        // File/image upload handling
        $fileFields = $fields->filter(fn($f) => in_array($f->field_type, ['file', 'image']));
        $fileSavingStore = '';
        $fileSavingUpdate = '';
        foreach ($fileFields as $f) {
            $col = $this->colName($f);
            // Store: upload all files, save paths as JSON array
            $fileSavingStore .= "        \$data['{$col}'] = [];\n"
                . "        if (\$request->hasFile('{$col}')) {\n"
                . "            foreach (\$request->file('{$col}') as \$__f) {\n"
                . "                \$data['{$col}'][] = \$__f->store('uploads/{$routeSlug}', 'public');\n"
                . "            }\n"
                . "        }\n";
            // Update: keep existing files (minus removed), append new uploads
            $fileSavingUpdate .= "        \$__toRemove_{$col} = \$request->input('{$col}_remove', []);\n"
                . "        \$__existing_{$col} = array_values(array_filter(\$request->input('{$col}_keep', []), fn(\$p) => !in_array(\$p, \$__toRemove_{$col})));\n"
                . "        \$__new_{$col} = [];\n"
                . "        if (\$request->hasFile('{$col}')) {\n"
                . "            foreach (\$request->file('{$col}') as \$__f) {\n"
                . "                \$__new_{$col}[] = \$__f->store('uploads/{$routeSlug}', 'public');\n"
                . "            }\n"
                . "        }\n"
                . "        \$data['{$col}'] = array_merge(\$__existing_{$col}, \$__new_{$col});\n";
        }
        // Remove file fields from validated $data (they are handled separately above)
        $fileColsUnset = '';
        foreach ($fileFields as $f) {
            $col = $this->colName($f);
            $fileColsUnset .= "        unset(\$data['{$col}']);\n";
        }

        // Dynamic options fetching
        $dynamicFetching = "        \$dynamicData = [];\n";
        $allFields = collect($fields);
        foreach($fields as $f) {
            if ($f->field_type === 'repeater') {
                foreach($f->repeater_columns ?? [] as $rc) {
                    $allFields->push((object)[
                        'field_type' => $rc['type'] ?? 'text',
                        'options' => $rc['options'] ?? [],
                        'column_name' => preg_replace('/[^a-z0-9_]/', '', strtolower($rc['key'] ?? 'item'))
                    ]);
                }
            }
        }

        foreach ($allFields as $f) {
            $col = ($f instanceof \App\Models\PageField) ? $this->colName($f) : ($f->column_name ?? '');
            if (empty($col)) continue;
            $opts = (array)($f->options ?? []);
            if (!empty($opts['dynamic']['enabled']) && !empty($opts['dynamic']['table'])) {
                $dyn = $opts['dynamic'];
                $table = addslashes($dyn['table']);
                $lbl = addslashes($dyn['label_col'] ?: 'name');
                $val = addslashes($dyn['value_col'] ?: 'id');
                $var = str_replace(['[', ']', ' '], '_', $col) . '_options';
                $dynamicFetching .= "        \$dynamicData['{$var}'] = \Illuminate\Support\Facades\DB::table('{$table}')->pluck('{$lbl}', '{$val}');\n";
            }
        }

        $repeaterFields = $fields->where('field_type', 'repeater');
        $with = $repeaterFields->isEmpty() ? '' : "->with([" . $repeaterFields->map(fn($f) => "'" . $this->colName($f) . "'")->implode(', ') . "])";

        $stub = "<?php\nnamespace App\Http\Controllers\Generated;\nuse App\Http\Controllers\Controller;\nuse App\Models\Generated\\{$modelName};\nuse App\Exports\Generated\\{$modelName}Export;\nuse App\Models\ExportLog;\nuse Maatwebsite\Excel\Facades\Excel;\nuse Illuminate\Http\Request;\nuse Illuminate\Support\Facades\Auth;\nuse Illuminate\Support\Facades\Storage;\n{$ruleImport}class {$modelName}Controller extends Controller\n{\n    public function index(Request \$request)\n    {\n        \$search = \$request->input('search');\n        \${$varPlural} = {$modelName}::query(){$with}->when(\$search, fn(\$q) => \$q->where(array_key_first((new {$modelName})->getFillable() ? array_flip((new {$modelName})->getFillable()) : []), 'like', \"%{\$search}%\"))->latest()->paginate(15)->withQueryString();\n        \$exportLogs = ExportLog::where('model', '{$modelName}')->latest()->take(20)->get();\n        return view('{$viewFolder}.index', compact('{$varPlural}', 'search', 'exportLogs'));\n    }\n    public function export()\n    {\n        \$data = {$modelName}::orderBy('id')->get();\n        \$hash = md5(\$data->toJson());\n        \$existing = ExportLog::where('model', '{$modelName}')->where('data_hash', \$hash)->latest()->first();\n        if (\$existing && Storage::disk('public')->exists(\$existing->file_path)) {\n            return Storage::disk('public')->download(\$existing->file_path, \$existing->file_name);\n        }\n        \$fileName = '{$routeSlug}_' . now()->format('Ymd_His') . '.xlsx';\n        \$filePath = 'exports/' . \$fileName;\n        Excel::store(new {$modelName}Export, \$filePath, 'public');\n        ExportLog::create(['model' => '{$modelName}', 'file_name' => \$fileName, 'file_path' => \$filePath, 'row_count' => \$data->count(), 'data_hash' => \$hash, 'user_id' => Auth::id()]);\n        return Storage::disk('public')->download(\$filePath, \$fileName);\n    }\n    public function exportDownload(ExportLog \$exportLog)\n    {\n        abort_if(\$exportLog->model !== '{$modelName}', 403);\n        abort_unless(Storage::disk('public')->exists(\$exportLog->file_path), 404);\n        return Storage::disk('public')->download(\$exportLog->file_path, \$exportLog->file_name);\n    }\n    public function create()\n    {\n{$dynamicFetching}        return view('{$viewFolder}.create', \$dynamicData);\n    }\n    public function store(Request \$request)\n    {\n        \$data = \$request->validate([\n{$storeLines}        ]);\n{$fileColsUnset}{$fileSavingStore}        \${$varName} = {$modelName}::create(\$data);\n{$repeaterSaving}        return redirect()->route('{$routeBase}.index')->with('success', 'Record created.');\n    }\n    public function show({$modelName} \${$varName}) { return view('{$viewFolder}.show', compact('{$varName}')); }\n    public function edit({$modelName} \${$varName})\n    {\n{$dynamicFetching}        return view('{$viewFolder}.edit', array_merge(compact('{$varName}'), \$dynamicData));\n    }\n    public function update(Request \$request, {$modelName} \${$varName})\n    {\n        \$data = \$request->validate([\n{$updateLines}        ]);\n{$fileColsUnset}{$fileSavingUpdate}        \${$varName}->update(\$data);\n{$repeaterSaving}        return redirect()->route('{$routeBase}.index')->with('success', 'Record updated.');\n    }\n    public function destroy({$modelName} \${$varName})\n    {\n        \${$varName}->delete();\n        return redirect()->route('{$routeBase}.index')->with('success', 'Record deleted.');\n    }\n}\n";
        file_put_contents("{$dir}/{$modelName}Controller.php", $stub);
    }

    private function fieldValidationRules($field, array $base, ?string $ignoreId = null): string
    {
        $rules = $base;
        $rules[] = match ($field->field_type) {
            'email' => "'email'",
            'url' => "'url'",
            'number' => "'integer'",
            'decimal', 'currency' => "'numeric'",
            'toggle', 'checkbox' => "'boolean'",
            'date', 'datetime' => "'date'",
            'rating' => "'integer', 'min:1', 'max:5'",
            'repeater' => "'array'",
            'image' => "'array'",
            'file' => "'array'",
            default => "'string'",
        };
        if ($field->column_length && !in_array($field->field_type, ['image', 'file']))
            $rules[] = "'max:{$field->column_length}'";
        if ($field->is_unique) {
            $col = $this->colName($field);
            $rules[] = $ignoreId
                ? "Rule::unique('{$field->table_name}', '{$col}')->ignore(\${$ignoreId})"
                : "Rule::unique('{$field->table_name}', '{$col}')";
        }
        return implode(', ', $rules);
    }

    // ── Views ──────────────────────────────────────────────────────────────────

    private function createViews(string $modelName, string $routeBase, string $viewFolder, $fields): void
    {
        $dir = resource_path("views/{$viewFolder}");
        if (!is_dir($dir))
            mkdir($dir, 0755, true);
        $varName = Str::camel($modelName);
        $varPlural = Str::camel(Str::plural($modelName));
        $title = Str::headline($modelName);
        $thCols = $tdCols = '';
        $hasFileFields = false;
        foreach ($fields as $f) {
            $col = $this->colName($f);
            $label = $f->label ?: Str::headline($col);
            $thCols .= "                <th class=\"px-6 py-3 text-xs font-semibold text-stone-500 tracking-wider\">{$label}</th>\n";
            // repeater fields are now relationships — show row count
            if ($f->field_type === 'repeater') {
                $tdCols .= "                <td class=\"px-1 py-1 text-stone-700\">{{ \${$varName}->{$col}->count() }} row(s)</td>\n";
            } elseif ($f->field_type === 'json') {
                $tdCols .= "                <td class=\"px-1 py-1 text-stone-700\">{{ is_array(\${$varName}->{$col}) ? count(\${$varName}->{$col}).' row(s)' : (\${$varName}->{$col} ?? '—') }}</td>\n";
            } elseif ($f->field_type === 'image') {
                $hasFileFields = true;
                $tdCols .= "                <td class=\"px-1 py-1\">\n"
                    . "                    @php \$__files = array_filter((array)(\${$varName}->{$col} ?? [])); @endphp\n"
                    . "                    @if(count(\$__files))\n"
                    . "                    <div class=\"flex items-center gap-1\">\n"
                    . "                        @foreach(\$__files as \$__fp)\n"
                    . "                        <button type=\"button\" onclick=\"openFilePreview('{{ Storage::url(\$__fp) }}', 'image', '{{ basename(\$__fp) }}')\" class=\"act-btn act-edit\" title=\"{{ basename(\$__fp) }}\">\n"
                    . "                            <svg fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\"/></svg>\n"
                    . "                        </button>\n"
                    . "                        @endforeach\n"
                    . "                    </div>\n"
                    . "                    @else\n"
                    . "                    <span class=\"text-stone-300\">—</span>\n"
                    . "                    @endif\n"
                    . "                </td>\n";
            } elseif ($f->field_type === 'file') {
                $hasFileFields = true;
                $tdCols .= "                <td class=\"px-1 py-1\">\n"
                    . "                    @php \$__files = array_filter((array)(\${$varName}->{$col} ?? [])); @endphp\n"
                    . "                    @if(count(\$__files))\n"
                    . "                    <div class=\"flex items-center gap-1\">\n"
                    . "                        @foreach(\$__files as \$__fp)\n"
                    . "                        <button type=\"button\" onclick=\"openFilePreview('{{ Storage::url(\$__fp) }}', 'file', '{{ basename(\$__fp) }}')\" class=\"act-btn act-edit\" title=\"{{ basename(\$__fp) }}\">\n"
                    . "                            <svg fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13\"/></svg>\n"
                    . "                        </button>\n"
                    . "                        @endforeach\n"
                    . "                    </div>\n"
                    . "                    @else\n"
                    . "                    <span class=\"text-stone-300\">—</span>\n"
                    . "                    @endif\n"
                    . "                </td>\n";
            } else {
                $tdCols .= "                <td class=\"px-1 py-1 text-stone-700\">{{ \${$varName}->{$col} ?? '—' }}</td>\n";
            }
        }
        file_put_contents("{$dir}/index.blade.php", $this->indexView($title, $routeBase, $varPlural, $varName, $thCols, $tdCols, $hasFileFields));
        file_put_contents("{$dir}/create.blade.php", $this->formView($title, $routeBase, $varName, $fields, false));
        file_put_contents("{$dir}/edit.blade.php", $this->formView($title, $routeBase, $varName, $fields, true));
        file_put_contents("{$dir}/show.blade.php", $this->showView($title, $routeBase, $varName, $fields));
    }

    private function indexView(string $title, string $routeBase, string $varPlural, string $varName, string $thCols, string $tdCols, bool $hasFileFields = false): string
    {
        $storageUse = $hasFileFields ? "@php use Illuminate\\Support\\Facades\\Storage; @endphp\n" : '';
        $filePreviewModal = $hasFileFields ? "\n{{-- File Preview Modal --}}\n<div id=\"filePreviewOverlay\" class=\"fixed inset-0 bg-black/60 z-50 hidden\" style=\"align-items:center;justify-content:center;\">\n    <div onclick=\"event.stopPropagation()\" class=\"bg-white rounded-2xl shadow-2xl w-full mx-4 overflow-hidden\" style=\"max-width:640px;\">\n        <div class=\"flex items-center justify-between px-5 py-4 border-b border-stone-100\">\n            <p id=\"filePreviewName\" class=\"text-sm font-semibold text-stone-800 truncate mr-4\"></p>\n            <div class=\"flex items-center gap-2 shrink-0\">\n                <a id=\"filePreviewDownload\" href=\"#\" download class=\"inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-stone-100 text-stone-600 hover:bg-stone-200 transition-colors\">\n                    <svg class=\"w-3.5 h-3.5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4\"/></svg>\n                    Download\n                </a>\n                <button onclick=\"closeFilePreview()\" class=\"w-8 h-8 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors\">\n                    <svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M6 18L18 6M6 6l12 12\"/></svg>\n                </button>\n            </div>\n        </div>\n        <div id=\"filePreviewBody\" class=\"p-5 flex items-center justify-center bg-stone-50\" style=\"min-height:200px;\">\n        </div>\n    </div>\n</div>\n<script>\nvar _imageExts = ['jpg','jpeg','png','gif','webp','svg','bmp','ico','tiff','avif'];\nvar _videoExts = ['mp4','webm','ogg','mov'];\nfunction _fileExt(name) { return (name.split('.').pop() || '').toLowerCase(); }\nfunction openFilePreview(url, fieldType, name) {\n    document.getElementById('filePreviewName').textContent = name;\n    document.getElementById('filePreviewDownload').href = url;\n    var body = document.getElementById('filePreviewBody');\n    var ext = _fileExt(name);\n    if (ext === 'pdf') {\n        body.style.padding = '0'; body.style.minHeight = '520px';\n        body.innerHTML = '<iframe src=\"' + url + '#toolbar=1&navpanes=0\" style=\"width:100%;height:520px;border:none;display:block;\" allowfullscreen></iframe>';\n    } else if (_imageExts.indexOf(ext) !== -1) {\n        body.style.padding = '20px'; body.style.minHeight = '220px';\n        body.innerHTML = '<img src=\"' + url + '\" alt=\"' + name + '\" style=\"max-height:460px;max-width:100%;border-radius:8px;object-fit:contain;\">';\n    } else if (_videoExts.indexOf(ext) !== -1) {\n        body.style.padding = '20px'; body.style.minHeight = '220px';\n        body.innerHTML = '<video controls style=\"max-height:420px;max-width:100%;border-radius:8px;\"><source src=\"' + url + '\"><p style=\"font-size:13px;color:#78716c;\">Your browser does not support video playback.</p></video>';\n    } else {\n        body.style.padding = '20px'; body.style.minHeight = '220px';\n        body.innerHTML = '<div style=\"display:flex;flex-direction:column;align-items:center;gap:12px;padding:32px 0;\"><svg style=\"width:48px;height:48px;color:#d4d4d4;\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\"/></svg><p style=\"font-size:13px;color:#78716c;\">No preview available for <strong>' + ext.toUpperCase() + '</strong> files.</p><a href=\"' + url + '\" target=\"_blank\" style=\"display:inline-flex;align-items:center;gap:6px;padding:8px 20px;border-radius:10px;background:#991b1b;color:#fff;font-size:13px;font-weight:500;text-decoration:none;\">Open File</a></div>';\n    }\n    document.getElementById('filePreviewOverlay').style.display = 'flex';\n}\nfunction closeFilePreview() {\n    document.getElementById('filePreviewOverlay').style.display = 'none';\n    var body = document.getElementById('filePreviewBody');\n    body.innerHTML = ''; body.style.padding = ''; body.style.minHeight = '220px';\n}\ndocument.getElementById('filePreviewOverlay').addEventListener('click', function(e) { if (e.target === this) closeFilePreview(); });\ndocument.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeFilePreview(); });\n</script>" : '';
        return "@extends('layouts.app')\n@section('content')\n{$storageUse}<div class=\"bg-white border border-stone-200 rounded-1xl overflow-hidden\">\n    <div class=\"px-6 py-5 border-b border-stone-100 flex items-center justify-between gap-4\">\n        <div>\n            <h3 class=\"text-sm font-semibold text-stone-800\">{$title}</h3>\n            <p class=\"text-xs text-stone-400 mt-0.5\">{{ \${$varPlural}->total() }} {{ Str::plural('record', \${$varPlural}->total()) }}</p>\n        </div>\n        <div class=\"flex items-center gap-3\">\n            <form method=\"GET\" action=\"{{ route('{$routeBase}.index') }}\">\n                <div class=\"flex items-center gap-2 border border-stone-300 rounded-xl px-3 py-2 focus-within:border-red-700 focus-within:ring-2 focus-within:ring-red-700/10 transition bg-white\">\n                    <svg class=\"w-4 h-4 text-stone-400 shrink-0\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z\"/></svg>\n                    <input type=\"text\" name=\"search\" value=\"{{ \$search ?? '' }}\" placeholder=\"Search…\" autocomplete=\"off\" class=\"text-sm outline-none border-none p-0 bg-transparent text-stone-700 placeholder-stone-400 w-40\" oninput=\"clearTimeout(window._st); window._st = setTimeout(() => this.form.submit(), 400)\">\n                    @if(!empty(\$search))\n                    <a href=\"{{ route('{$routeBase}.index') }}\" class=\"text-stone-400 hover:text-stone-600 transition shrink-0\"><svg class=\"w-3.5 h-3.5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M6 18L18 6M6 6l12 12\"/></svg></a>\n                    @endif\n                </div>\n            </form>\n            <a href=\"{{ route('{$routeBase}.create') }}\" class=\"inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-red-800 hover:bg-red-700 text-white text-sm font-medium transition-colors shadow-sm whitespace-nowrap\">\n                <svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 4v16m8-8H4\"/></svg>\n                Add New\n            </a>\n            <div class=\"inline-flex rounded-xl overflow-hidden shadow-sm\">\n                <a href=\"{{ route('{$routeBase}.export') }}\" class=\"inline-flex items-center gap-1.5 px-4 py-2 bg-green-700 hover:bg-green-600 text-white text-sm font-medium transition-colors whitespace-nowrap\">\n                    <svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4\"/></svg>\n                    Export\n                </a>\n                <button onclick=\"openExportLog()\" class=\"inline-flex items-center px-2.5 py-2 bg-green-800 hover:bg-green-700 text-white text-sm transition-colors border-l border-green-600\" title=\"Export history\">\n                    <svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z\"/></svg>\n                </button>\n            </div>\n        </div>\n    </div>\n    @if(\${$varPlural}->isEmpty())\n    <div class=\"flex flex-col items-center justify-center py-20 text-center\">\n        <div class=\"w-14 h-14 rounded-1xl bg-stone-100 flex items-center justify-center mb-4\"><svg class=\"w-7 h-7 text-stone-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\"/></svg></div>\n        <p class=\"text-sm font-medium text-stone-600\">No records yet</p>\n        <p class=\"text-xs text-stone-400 mt-1\">Click \"Add New\" to get started.</p>\n    </div>\n    @else\n    <table class=\"w-full text-sm\">\n        <thead>\n            <tr class=\"border-b border-stone-100 bg-stone-50 text-left\">\n                <th class=\"px-6 py-3 text-xs font-semibold text-stone-500 tracking-wider w-12\">#</th>\n{$thCols}                <th class=\"px-6 py-3 text-xs font-semibold text-stone-500 tracking-wider text-right\">Actions</th>\n            </tr>\n        </thead>\n        <tbody class=\"divide-y divide-stone-100\">\n            @foreach(\${$varPlural} as \$index => \${$varName})\n            <tr class=\"hover:bg-stone-50 transition-colors\">\n                <td class=\"px-6 py-1 text-stone-400\">{{ \${$varPlural}->firstItem() + \$index }}</td>\n{$tdCols}                <td class=\"px-6 py-1 text-right\">\n                    <div class=\"act-group justify-end\">\n                        <a href=\"{{ route('{$routeBase}.show', \${$varName}) }}\" class=\"act-btn act-edit\" title=\"View\"><svg fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15 12a3 3 0 11-6 0 3 3 0 016 0z\"/><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z\"/></svg></a>\n                        <a href=\"{{ route('{$routeBase}.edit', \${$varName}) }}\" class=\"act-btn act-edit\" title=\"Edit\"><svg fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z\"/></svg></a>\n                        <form method=\"POST\" action=\"{{ route('{$routeBase}.destroy', \${$varName}) }}\" onsubmit=\"return confirm('Delete this record?')\" style=\"display:contents\">@csrf @method('DELETE')<button type=\"submit\" class=\"act-btn act-delete\" title=\"Delete\"><svg fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16\"/></svg></button></form>\n                    </div>\n                </td>\n            </tr>\n            @endforeach\n        </tbody>\n    </table>\n    @if(\${$varPlural}->hasPages())\n    <div class=\"px-6 py-1 border-t border-stone-100 flex items-center justify-between gap-4\">\n        <p class=\"text-xs text-stone-400\">Showing {{ \${$varPlural}->firstItem() }}–{{ \${$varPlural}->lastItem() }} of {{ \${$varPlural}->total() }} results</p>\n        <div class=\"flex items-center gap-1\">\n            @if(\${$varPlural}->onFirstPage())<span class=\"inline-flex items-center justify-center w-8 h-8 rounded-lg text-stone-300 cursor-not-allowed\"><svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15 19l-7-7 7-7\"/></svg></span>@else<a href=\"{{ \${$varPlural}->previousPageUrl() }}\" class=\"inline-flex items-center justify-center w-8 h-8 rounded-lg text-stone-500 hover:bg-stone-100 transition-colors\"><svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15 19l-7-7 7-7\"/></svg></a>@endif\n            @foreach(\${$varPlural}->getUrlRange(1, \${$varPlural}->lastPage()) as \$pg => \$url)<a href=\"{{ \$url }}\" class=\"inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-medium transition-colors {{ \$pg == \${$varPlural}->currentPage() ? 'bg-red-800 text-white' : 'text-stone-600 hover:bg-stone-100' }}\">{{ \$pg }}</a>@endforeach\n            @if(\${$varPlural}->hasMorePages())<a href=\"{{ \${$varPlural}->nextPageUrl() }}\" class=\"inline-flex items-center justify-center w-8 h-8 rounded-lg text-stone-500 hover:bg-stone-100 transition-colors\"><svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 5l7 7-7 7\"/></svg></a>@else<span class=\"inline-flex items-center justify-center w-8 h-8 rounded-lg text-stone-300 cursor-not-allowed\"><svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 5l7 7-7 7\"/></svg></span>@endif\n        </div>\n    </div>\n    @endif\n    @endif\n</div>\n\n{{-- Export Log Offcanvas --}}\n<div id=\"exportLogOverlay\" onclick=\"closeExportLog()\" class=\"fixed inset-0 bg-black/40 z-40 hidden\"></div>\n<div id=\"exportLogPanel\" class=\"fixed top-0 right-0 h-full w-96 bg-white shadow-2xl z-50 translate-x-full transition-transform duration-300 flex flex-col\">\n    <div class=\"flex items-center justify-between px-5 py-4 border-b border-stone-100\">\n        <div>\n            <h4 class=\"text-sm font-semibold text-stone-800\">Export History</h4>\n            <p class=\"text-xs text-stone-400 mt-0.5\">{$title}</p>\n        </div>\n        <button onclick=\"closeExportLog()\" class=\"w-8 h-8 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors\"><svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M6 18L18 6M6 6l12 12\"/></svg></button>\n    </div>\n    <div class=\"flex-1 overflow-y-auto p-4 space-y-2\">\n        @forelse(\$exportLogs as \$log)\n        <div class=\"flex items-center justify-between gap-3 px-4 py-3 rounded-xl border border-stone-100 bg-stone-50 hover:bg-white hover:border-stone-200 transition-colors\">\n            <div class=\"min-w-0\">\n                <p class=\"text-xs font-medium text-stone-700 truncate\">{{ \$log->file_name }}</p>\n                <p class=\"text-xs text-stone-400 mt-0.5\">{{ \$log->row_count }} rows &middot; {{ \$log->created_at->format('d M Y, H:i') }}</p>\n                @if(\$log->user)<p class=\"text-xs text-stone-400\">by {{ \$log->user->name }}</p>@endif\n            </div>\n            <a href=\"{{ route('{$routeBase}.export.download', \$log) }}\" class=\"shrink-0 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-green-50 text-green-700 hover:bg-green-100 transition-colors\"><svg class=\"w-3.5 h-3.5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4\"/></svg>Download</a>\n        </div>\n        @empty\n        <div class=\"flex flex-col items-center justify-center py-16 text-center\">\n            <div class=\"w-12 h-12 rounded-1xl bg-stone-100 flex items-center justify-center mb-3\"><svg class=\"w-6 h-6 text-stone-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\"/></svg></div>\n            <p class=\"text-sm font-medium text-stone-500\">No exports yet</p>\n            <p class=\"text-xs text-stone-400 mt-1\">Click Export to generate your first file.</p>\n        </div>\n        @endforelse\n    </div>\n</div>\n<script>\nfunction openExportLog(){document.getElementById('exportLogOverlay').classList.remove('hidden');document.getElementById('exportLogPanel').classList.remove('translate-x-full');}\nfunction closeExportLog(){document.getElementById('exportLogOverlay').classList.add('hidden');document.getElementById('exportLogPanel').classList.add('translate-x-full');}\n</script>\n{$filePreviewModal}\n@endsection\n";
    }

    private function formView(string $title, string $routeBase, string $varName, $fields, bool $isEdit): string
    {
        $action = $isEdit ? "route('{$routeBase}.update', \${$varName})" : "route('{$routeBase}.store')";
        $method = $isEdit ? "@method('PUT')" : '';
        $heading = $isEdit ? "Edit {$title}" : "New {$title}";
        $subtext = $isEdit ? 'Update the record details.' : 'Fill in the details below.';
        $btnText = $isEdit ? 'Update Record' : 'Create Record';
        $inputs = '';
        foreach ($fields as $f) {
            $col = $this->colName($f);
            $label = $f->label ?: Str::headline($col);
            $placeholder = $f->placeholder ?: $label;
            $oldVal = $isEdit ? "\${$varName}->{$col}" : "old('{$col}')";
            $inputs .= $this->formInput($f, $col, $label, $placeholder, $oldVal, $varName);
        }
        $hasRepeater = $fields->contains('field_type', 'repeater');
        $repeaterScript = $hasRepeater ? "\n@push('scripts')\n<script>\ndocument.addEventListener('alpine:init', () => {\n    Alpine.data('repeaterField', (col) => ({\n        addRow() {\n            const tpl = document.getElementById('repeater_' + col + '_tpl');\n            const body = document.getElementById('repeater_' + col + '_body');\n            const clone = tpl.content.cloneNode(true);\n            const idx = body.querySelectorAll('tr').length;\n            clone.querySelectorAll('[name]').forEach(el => { el.name = el.name.replace(/__IDX__/g, idx); });\n            body.appendChild(clone);\n            window.renumberRepeater(col);\n        }\n    }));\n});\nwindow.renumberRepeater = function(col) {\n    document.querySelectorAll('#repeater_' + col + '_body tr').forEach((tr, i) => {\n        const num = tr.querySelector('.row-num');\n        if (num) num.textContent = i + 1;\n        tr.querySelectorAll('[name]').forEach(el => { el.name = el.name.replace(/\[\d+\]/g, '[' + i + ']'); });\n    });\n};\n</script>\n@endpush\n" : '';
        $ic = 'w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10';
        return "@extends('layouts.app')\n@section('content')\n@php use Illuminate\\Support\\Facades\\Storage; @endphp\n<div class=\"bg-white border border-stone-200 rounded-1xl overflow-hidden\">\n    <div class=\"px-6 py-5 border-b border-stone-100 flex items-center justify-between\">\n        <div>\n            <h3 class=\"text-sm font-semibold text-stone-800\">{$heading}</h3>\n            <p class=\"text-xs text-stone-400 mt-0.5\">{$subtext}</p>\n        </div>\n        <a href=\"{{ route('{$routeBase}.index') }}\" class=\"inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-stone-100 text-stone-600 hover:bg-stone-200 transition-colors\"><svg class=\"w-3.5 h-3.5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15 19l-7-7 7-7\"/></svg>Back</a>\n    </div>\n    <form method=\"POST\" action=\"{{ {$action} }}\" enctype=\"multipart/form-data\">\n        @csrf {$method}\n        <div class=\"p-6\">\n            @if(\$errors->any())\n            <div class=\"mb-5 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl\">Please fix the errors below.</div>\n            @endif\n            <div class=\"grid grid-cols-3 gap-5\">\n{$inputs}            </div>\n        </div>\n        <div class=\"px-6 py-1 bg-stone-50 border-t border-stone-100 flex items-center justify-end gap-3\">\n            <a href=\"{{ route('{$routeBase}.index') }}\" class=\"px-4 py-2.5 rounded-xl text-sm font-medium text-stone-600 bg-white border border-stone-300 hover:bg-stone-50 transition-colors\">Cancel</a>\n            <button type=\"submit\" class=\"inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-800 hover:bg-red-700 text-white text-sm font-medium transition-colors shadow-sm\"><svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M5 13l4 4L19 7\"/></svg>{$btnText}</button>\n        </div>\n    </form>\n</div>\n@endsection\n{$repeaterScript}";
    }

    private function formInput($field, string $col, string $label, string $placeholder, string $oldVal, string $varName): string
    {
        $req = $field->is_required ? ' <span class="text-red-500">*</span>' : '';
        $span = match ((int) ($field->col_span ?? 1)) {
            2 => 'col-span-2',
            3 => 'col-span-3',
            default => 'col-span-1'
        };
        $ic = "w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('{$col}') border-red-400 bg-red-50 @enderror";
        $err = "                    @error('{$col}')<p class=\"mt-1.5 text-xs text-red-600\">{{ \$message }}</p>@enderror\n";
        $base = "                <div class=\"{$span}\">\n                    <label class=\"block text-sm font-medium text-stone-700 mb-1.5\">{$label}{$req}</label>\n";

        if ($field->field_type === 'repeater') {
            return $base . $this->repeaterFormInput($field, $col, $label, $oldVal, $varName) . $err . "                </div>\n";
        }

        $input = $this->renderField($field->field_type, $col, $oldVal, $placeholder, $ic, $field->options);
        return $base . "                    " . $input . "\n" . $err . "                </div>\n";
    }

    private function renderField($type, $name, $valueExpression, $placeholder, $ic, $options = []): string
    {
        switch ($type) {
            case 'content':
            case 'textarea':
                return "<textarea name=\"{$name}\" rows=\"4\" placeholder=\"{$placeholder}\" class=\"{$ic} resize-none\">{{ {$valueExpression} }}</textarea>";
            case 'checkbox':
            case 'toggle':
                $id = str_replace(['[', ']', ' '], '_', $name);
                $checked = (str_contains($valueExpression, '__IDX__') || str_contains($valueExpression, '$__ri'))
                    ? "((isset({$valueExpression}) && {$valueExpression}) ? 'checked' : '')"
                    : "({$valueExpression} ? 'checked' : '')";
                return "<div class=\"flex items-center gap-2 mt-1\"><input type=\"checkbox\" name=\"{$name}\" value=\"1\" id=\"{$id}\" {{ {$checked} }} class=\"w-4 h-4 rounded border-stone-300 text-red-700 focus:ring-red-700\"><label for=\"{$id}\" class=\"text-sm text-stone-600\">Enabled</label></div>";
            case 'select':
                $opts = $this->getSelectOptionsMarkup($options, $valueExpression, $name);
                return "<select name=\"{$name}\" class=\"{$ic}\">{$opts}</select>";
            case 'color':
                return "<input type=\"color\" name=\"{$name}\" value=\"{{ {$valueExpression} ?? '#000000' }}\" class=\"h-10 w-20 rounded-xl border border-stone-300 cursor-pointer\">";
            case 'image':
            case 'file':
                $isImage = $type === 'image';
                $accept = $isImage ? " accept=\"image/*\"" : '';
                $isEditMode = str_starts_with(trim($valueExpression), '$');
                if ($isEditMode) {
                    // Show existing files with individual remove checkboxes
                    $preview = "@if(!empty({$valueExpression}))\n"
                        . "                    <div class=\"mb-3 space-y-1.5\">\n"
                        . "                        @foreach((array){$valueExpression} as \$__fi => \$__fp)\n"
                        . "                        <div class=\"flex items-center gap-2 px-3 py-2 bg-stone-50 border border-stone-200 rounded-lg\">\n";
                    if ($isImage) {
                        $preview .= "                            <img src=\"{{ Storage::url(\$__fp) }}\" class=\"h-10 w-10 rounded object-cover border border-stone-200 shrink-0\">\n";
                    } else {
                        $preview .= "                            <svg class=\"w-4 h-4 text-stone-400 shrink-0\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13\"/></svg>\n";
                    }
                    $preview .= "                            <span class=\"text-xs text-stone-600 truncate flex-1\">{{ basename(\$__fp) }}</span>\n"
                        . "                            <label class=\"flex items-center gap-1 text-xs text-red-600 cursor-pointer shrink-0\">\n"
                        . "                                <input type=\"checkbox\" name=\"{$name}_remove[]\" value=\"{{ \$__fp }}\" class=\"w-3.5 h-3.5 rounded border-stone-300 text-red-600\">\n"
                        . "                                Remove\n"
                        . "                            </label>\n"
                        . "                            <input type=\"hidden\" name=\"{$name}_keep[]\" value=\"{{ \$__fp }}\">\n"
                        . "                        </div>\n"
                        . "                        @endforeach\n"
                        . "                    </div>\n"
                        . "                    @endif";
                    return "{$preview}\n                    <input type=\"file\" name=\"{$name}[]\" multiple{$accept} class=\"w-full text-sm text-stone-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-red-800 file:text-white file:text-xs file:font-medium\">\n"
                        . "                    <p class=\"mt-1 text-xs text-stone-400\">Select one or more files. Existing files are kept unless removed.</p>";
                }
                return "<input type=\"file\" name=\"{$name}[]\" multiple{$accept} class=\"w-full text-sm text-stone-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-red-800 file:text-white file:text-xs file:font-medium\">";
            default:
                $htmlType = $this->htmlInputType($type);
                // Handle "use current date/time as default" option for date/datetime/time fields
                if (in_array($type, ['date', 'datetime', 'time']) && !empty($options['use_current_date'])) {
                    $defaultExpr = match ($type) {
                        'date' => "date('Y-m-d')",
                        'datetime' => "date('Y-m-d\\TH:i')",
                        'time' => "date('H:i')",
                    };
                    return "<input type=\"{$htmlType}\" name=\"{$name}\" value=\"{{ {$valueExpression} ?? {$defaultExpr} }}\" placeholder=\"{$placeholder}\" class=\"{$ic}\">";
                }
                return "<input type=\"{$htmlType}\" name=\"{$name}\" value=\"{{ {$valueExpression} }}\" placeholder=\"{$placeholder}\" class=\"{$ic}\">";
        }
    }

    private function getSelectOptionsMarkup($options, $valueExpression, $name = ''): string
    {
        $markup = '<option value="">-- Select --</option>';
        
        // Dynamic options handling
        if (!empty($options['dynamic']['enabled']) && !empty($options['dynamic']['table'])) {
            $var = str_replace(['[', ']', ' '], '_', $name) . '_options';
            $markup .= "\n                        @isset(\${$var})\n";
            $markup .= "                            @foreach(\${$var} as \$val => \$lab)\n";
            $markup .= "                                <option value=\"{{ \$val }}\" {{ ({$valueExpression} ?? '') == \$val ? 'selected' : '' }}>{{ \$lab }}</option>\n";
            $markup .= "                            @endforeach\n";
            $markup .= "                        @endisset\n";
            return $markup;
        }

        $static = $options['static'] ?? [];
        foreach ($static as $opt) {
            $val = htmlspecialchars($opt['value']);
            $lab = htmlspecialchars($opt['label']);
            $selected = str_contains($valueExpression, '__IDX__')
                ? "" // Template mode
                : "{{ ({$valueExpression} ?? '') == '{$val}' ? 'selected' : '' }}";
            $markup .= "<option value=\"{$val}\" {$selected}>{$lab}</option>";
        }
        return $markup;
    }

    private function htmlInputType(string $type): string
    {
        return match ($type) {
            'number', 'rating', 'currency', 'decimal' => 'number',
            'email' => 'email',
            'phone' => 'tel',
            'url' => 'url',
            'password' => 'password',
            'date' => 'date',
            'datetime' => 'datetime-local',
            'time' => 'time',
            default => 'text',
        };
    }

    private function repeaterFormInput($field, string $col, string $label, string $oldVal, string $varName): string
    {
        $cols = $field->repeater_columns ?? [['key' => 'item', 'label' => 'Item', 'type' => 'text', 'required' => false, 'default' => '']];
        $ic = 'w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition';

        // Build headers
        $ths = '';
        foreach ($cols as $c) {
            $r = !empty($c['required']) ? ' <span class=\"text-red-500\">*</span>' : '';
            $ths .= "                        <th class=\"px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider\">{$c['label']}{$r}</th>\n";
        }

        // Build template columns
        $tds = '';
        foreach ($cols as $c) {
            $scKey = preg_replace('/[^a-z0-9_]/', '', strtolower($c['key'] ?? 'item'));
            $name = "{$col}[__IDX__][{$scKey}]";
            $options = ['static' => $c['options'] ?? []];
            $input = $this->renderField($c['type'] ?? 'text', $name, "''", '', $ic, $options);
            $tds .= "                        <td class=\"px-2 py-1.5\">{$input}</td>\n";
        }

        // Build edit mode rows
        $editRows = "@if(isset(\${$varName}) && \${$varName}->{$col}->count())\n                    @foreach(\${$varName}->{$col} as \$__ri => \$__row)\n                    <tr>\n                        <td class=\"px-3 py-1.5 text-stone-400 text-sm row-num\">{{ \$__ri + 1 }}</td>\n";
        foreach ($cols as $c) {
            $scKey = preg_replace('/[^a-z0-9_]/', '', strtolower($c['key'] ?? 'item'));
            $name = "{$col}[{{ \$__ri }}][{$scKey}]";
            $valExp = "\$__row->{$scKey}";
            $options = ['static' => $c['options'] ?? []];
            $input = $this->renderField($c['type'] ?? 'text', $name, $valExp, '', $ic, $options);
            $editRows .= "                        <td class=\"px-2 py-1.5\">{$input}</td>\n";
        }
        $editRows .= "                        <td class=\"px-2 py-1.5 text-center\"><button type=\"button\" onclick=\"this.closest('tr').remove(); window.renumberRepeater('{$col}')\" class=\"w-6 h-6 inline-flex items-center justify-center rounded bg-red-600 hover:bg-red-700 text-white text-xs font-bold\">−</button></td>\n                    </tr>\n                    @endforeach\n                    @endif\n";

        return <<<HTML
                                <div x-data="repeaterField('{$col}')" class="border border-stone-200 rounded-xl overflow-hidden">
                                    <table class="w-full text-sm" id="repeater_{$col}">
                                        <thead class="bg-stone-800 text-white">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-semibold w-8">#</th>
            {$ths}                                    <th class="px-3 py-2 w-10"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="repeater_{$col}_body">
            {$editRows}                            </tbody>
                                    </table>
                                    <div class="px-3 py-2 bg-stone-50 border-t border-stone-100">
                                        <button type="button" @click="addRow()"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-stone-800 hover:bg-stone-700 text-white text-xs font-medium transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            Add Row
                                        </button>
                                    </div>
                                </div>
                                <template id="repeater_{$col}_tpl">
                                    <tr>
                                        <td class="px-3 py-1.5 text-stone-400 text-sm row-num"></td>
            {$tds}                            <td class="px-2 py-1.5 text-center"><button type="button" onclick="this.closest('tr').remove(); window.renumberRepeater('{$col}')" class="w-6 h-6 inline-flex items-center justify-center rounded bg-red-600 hover:bg-red-700 text-white text-xs font-bold">−</button></td>
                                    </tr>
                                </template>
            HTML;
    }

    private function showView(string $title, string $routeBase, string $varName, $fields): string
    {
        $inputs = '';
        foreach ($fields as $f) {
            $col = $this->colName($f);
            $label = $f->label ?: Str::headline($col);
            $span = match ((int) ($f->col_span ?? 1)) {
                2 => 'col-span-2',
                3 => 'col-span-3',
                default => 'col-span-1'
            };

            if ($f->field_type === 'repeater') {
                $subCols = $f->repeater_columns ?? [];
                $thCells = '';
                foreach ($subCols as $c) {
                    $thCells .= '<th class="px-3 py-2 text-left text-xs font-semibold text-stone-500">' . htmlspecialchars($c['label'] ?? $c['key']) . '</th>';
                }
                $inputs .= "            <div class=\"{$span}\">\n"
                    . "                <label class=\"block text-sm font-medium text-stone-700 mb-1.5\">{$label}</label>\n"
                    . "                @php \$__rows = \${$varName}->{$col}; @endphp\n"
                    . "                @if(\$__rows->count())\n"
                    . "                <div class=\"border border-stone-200 rounded-xl overflow-hidden\">\n"
                    . "                    <table class=\"w-full text-sm\"><thead class=\"bg-stone-50 border-b border-stone-100\"><tr><th class=\"px-3 py-2 text-left text-xs font-semibold text-stone-500\">#</th>{$thCells}</tr></thead>\n"
                    . "                    <tbody class=\"divide-y divide-stone-100\">\n"
                    . "                    @foreach(\$__rows as \$__ri => \$__row)<tr><td class=\"px-3 py-2 text-stone-400 text-xs\">{{ \$__ri+1 }}</td>";
                foreach ($subCols as $c) {
                    $scKey = preg_replace('/[^a-z0-9_]/', '', strtolower($c['key'] ?? 'item'));
                    $inputs .= "<td class=\"px-3 py-2 text-stone-700 text-xs\">{{ \$__row->{$scKey} ?? '—' }}</td>";
                }
                $inputs .= "</tr>@endforeach\n"
                    . "                    </tbody></table>\n"
                    . "                </div>\n"
                    . "                @else\n"
                    . "                <p class=\"text-sm text-stone-400\">No rows.</p>\n"
                    . "                @endif\n"
                    . "            </div>\n";
            } elseif ($f->field_type === 'json') {
                $subCols = $f->repeater_columns ?? [];
                $thCells = '';
                foreach ($subCols as $c) {
                    $thCells .= '<th class="px-3 py-2 text-left text-xs font-semibold text-stone-500">' . htmlspecialchars($c['label'] ?? $c['key']) . '</th>';
                }
                $inputs .= "            <div class=\"{$span}\">\n"
                    . "                <label class=\"block text-sm font-medium text-stone-700 mb-1.5\">{$label}</label>\n"
                    . "                @php \$__rows = \${$varName}->{$col}; @endphp\n"
                    . "                @if(is_array(\$__rows) && count(\$__rows))\n"
                    . "                <div class=\"border border-stone-200 rounded-xl overflow-hidden\">\n"
                    . "                    <table class=\"w-full text-sm\"><thead class=\"bg-stone-50 border-b border-stone-100\"><tr><th class=\"px-3 py-2 text-left text-xs font-semibold text-stone-500\">#</th>{$thCells}</tr></thead>\n"
                    . "                    <tbody class=\"divide-y divide-stone-100\">\n"
                    . "                    @foreach(\$__rows as \$__ri => \$__row)<tr><td class=\"px-3 py-2 text-stone-400 text-xs\">{{ \$__ri+1 }}</td>@foreach(\$__row as \$__v)<td class=\"px-3 py-2 text-stone-700 text-xs\">{{ \$__v ?? '—' }}</td>@endforeach</tr>@endforeach\n"
                    . "                    </tbody></table>\n"
                    . "                </div>\n"
                    . "                @else\n"
                    . "                <p class=\"text-sm text-stone-400\">No rows.</p>\n"
                    . "                @endif\n"
                    . "            </div>\n";
            } elseif ($f->field_type === 'image') {
                $inputs .= "            <div class=\"{$span}\">\n"
                    . "                <label class=\"block text-sm font-medium text-stone-700 mb-1.5\">{$label}</label>\n"
                    . "                @php \$__files = array_filter((array)(\${$varName}->{$col} ?? [])); @endphp\n"
                    . "                @if(count(\$__files))\n"
                    . "                <div class=\"flex flex-wrap gap-2\">\n"
                    . "                    @foreach(\$__files as \$__fp)\n"
                    . "                    <button type=\"button\" onclick=\"openFilePreview('{{ Storage::url(\$__fp) }}', 'file', '{{ basename(\$__fp) }}')\" class=\"group relative rounded-lg border border-stone-200 overflow-hidden hover:border-red-300 transition focus:outline-none\" title=\"{{ basename(\$__fp) }}\">\n"
                    . "                        <img src=\"{{ Storage::url(\$__fp) }}\" alt=\"{{ basename(\$__fp) }}\" class=\"h-20 w-20 object-cover\" onerror=\"this.style.display='none';this.nextElementSibling.style.display='flex'\">\n"
                    . "                        <span style=\"display:none;\" class=\"h-20 w-20 flex-col items-center justify-center bg-stone-50 gap-1\"><svg class=\"w-6 h-6 text-stone-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\"/></svg><span class=\"text-xs text-stone-400 uppercase\">{{ strtoupper(pathinfo(\$__fp, PATHINFO_EXTENSION)) }}</span></span>\n"
                    . "                        <span class=\"absolute inset-0 bg-black/0 group-hover:bg-black/10 transition flex items-center justify-center\"><svg class=\"w-5 h-5 text-white opacity-0 group-hover:opacity-100 drop-shadow transition\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7\"/></svg></span>\n"
                    . "                    </button>\n"
                    . "                    @endforeach\n"
                    . "                </div>\n"
                    . "                @else\n"
                    . "                <p class=\"text-sm text-stone-400\">—</p>\n"
                    . "                @endif\n"
                    . "            </div>\n";
            } elseif ($f->field_type === 'file') {
                $inputs .= "            <div class=\"{$span}\">\n"
                    . "                <label class=\"block text-sm font-medium text-stone-700 mb-1.5\">{$label}</label>\n"
                    . "                @php \$__files = array_filter((array)(\${$varName}->{$col} ?? [])); @endphp\n"
                    . "                @if(count(\$__files))\n"
                    . "                <div class=\"flex flex-wrap gap-2\">\n"
                    . "                    @foreach(\$__files as \$__fp)\n"
                    . "                    <button type=\"button\" onclick=\"openFilePreview('{{ Storage::url(\$__fp) }}', 'file', '{{ basename(\$__fp) }}')\" class=\"group relative rounded-lg border border-stone-200 overflow-hidden hover:border-red-300 transition focus:outline-none\" title=\"{{ basename(\$__fp) }}\">\n"
                    . "                        <img src=\"{{ Storage::url(\$__fp) }}\" alt=\"{{ basename(\$__fp) }}\" class=\"h-20 w-20 object-cover\" onerror=\"this.style.display='none';this.nextElementSibling.style.display='flex'\">\n"
                    . "                        <span style=\"display:none;\" class=\"h-20 w-20 flex-col items-center justify-center bg-stone-50 gap-1\"><svg class=\"w-6 h-6 text-stone-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\"/></svg><span class=\"text-xs text-stone-400 uppercase\">{{ strtoupper(pathinfo(\$__fp, PATHINFO_EXTENSION)) }}</span></span>\n"
                    . "                        <span class=\"absolute inset-0 bg-black/0 group-hover:bg-black/10 transition flex items-center justify-center\"><svg class=\"w-5 h-5 text-white opacity-0 group-hover:opacity-100 drop-shadow transition\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7\"/></svg></span>\n"
                    . "                    </button>\n"
                    . "                    @endforeach\n"
                    . "                </div>\n"
                    . "                @else\n"
                    . "                <p class=\"text-sm text-stone-400\">—</p>\n"
                    . "                @endif\n"
                    . "            </div>\n";
            } else {
                $inputs .= "            <div class=\"{$span}\">\n                <label class=\"block text-sm font-medium text-stone-700 mb-1.5\">{$label}</label>\n                <input type=\"text\" disabled value=\"{{ \${$varName}->{$col} ?? '—' }}\" class=\"w-full px-3.5 py-2.5 text-sm border rounded-xl border-stone-200 bg-stone-50 text-stone-600 cursor-not-allowed\">\n            </div>\n";
            }
        }
        return "@extends('layouts.app')\n@section('content')\n@php use Illuminate\\Support\\Facades\\Storage; @endphp\n<div class=\"bg-white border border-stone-200 rounded-1xl overflow-hidden\">\n    <div class=\"px-6 py-5 border-b border-stone-100 flex items-center justify-between\">\n        <div>\n            <h3 class=\"text-sm font-semibold text-stone-800\">{$title} — Detail</h3>\n            <p class=\"text-xs text-stone-400 mt-0.5\">Record #{{ \${$varName}->id }}</p>\n        </div>\n        <a href=\"{{ route('{$routeBase}.index') }}\" class=\"inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-stone-100 text-stone-600 hover:bg-stone-200 transition-colors\"><svg class=\"w-3.5 h-3.5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15 19l-7-7 7-7\"/></svg>Back</a>\n    </div>\n    <div class=\"p-6\">\n        <div class=\"grid grid-cols-3 gap-5\">\n{$inputs}        </div>\n    </div>\n    <div class=\"px-6 py-1 bg-stone-50 border-t border-stone-100 flex items-center justify-end\">\n        <a href=\"{{ route('{$routeBase}.edit', \${$varName}) }}\" class=\"inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-800 hover:bg-red-700 text-white text-sm font-medium transition-colors shadow-sm\"><svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z\"/></svg>Edit</a>\n    </div>\n</div>\n\n{{-- File Preview Modal --}}\n<div id=\"filePreviewOverlay\" class=\"fixed inset-0 bg-black/60 z-50 hidden\" style=\"align-items:center;justify-content:center;\">\n    <div onclick=\"event.stopPropagation()\" class=\"bg-white rounded-2xl shadow-2xl w-full mx-4 overflow-hidden\" style=\"max-width:720px;\">\n        <div class=\"flex items-center justify-between px-5 py-4 border-b border-stone-100\">\n            <p id=\"filePreviewName\" class=\"text-sm font-semibold text-stone-800 truncate mr-4\"></p>\n            <div class=\"flex items-center gap-2 shrink-0\">\n                <a id=\"filePreviewDownload\" href=\"#\" download class=\"inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-stone-100 text-stone-600 hover:bg-stone-200 transition-colors\"><svg class=\"w-3.5 h-3.5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4\"/></svg>Download</a>\n                <button onclick=\"closeFilePreview()\" class=\"w-8 h-8 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors\"><svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M6 18L18 6M6 6l12 12\"/></svg></button>\n            </div>\n        </div>\n        <div id=\"filePreviewBody\" class=\"flex items-center justify-center bg-stone-50\" style=\"min-height:220px;\"></div>\n    </div>\n</div>\n<script>\nvar _imageExts=['jpg','jpeg','png','gif','webp','svg','bmp','ico','tiff','avif'];\nvar _videoExts=['mp4','webm','ogg','mov'];\nfunction _fileExt(n){return(n.split('.').pop()||'').toLowerCase();}\nfunction openFilePreview(url,ft,name){\n    document.getElementById('filePreviewName').textContent=name;\n    document.getElementById('filePreviewDownload').href=url;\n    var b=document.getElementById('filePreviewBody'),ext=_fileExt(name);\n    if(ext==='pdf'){b.style.padding='0';b.style.minHeight='520px';b.innerHTML='<iframe src=\"'+url+'#toolbar=1&navpanes=0\" style=\"width:100%;height:520px;border:none;display:block;\" allowfullscreen></iframe>';}\n    else if(_imageExts.indexOf(ext)!==-1){b.style.padding='20px';b.style.minHeight='220px';b.innerHTML='<img src=\"'+url+'\" alt=\"'+name+'\" style=\"max-height:460px;max-width:100%;border-radius:8px;object-fit:contain;\">';\n    }else if(_videoExts.indexOf(ext)!==-1){b.style.padding='20px';b.style.minHeight='220px';b.innerHTML='<video controls style=\"max-height:420px;max-width:100%;border-radius:8px;\"><source src=\"'+url+'\"><p style=\"font-size:13px;color:#78716c;\">Your browser does not support video playback.</p></video>';\n    }else{b.style.padding='20px';b.style.minHeight='220px';b.innerHTML='<div style=\"display:flex;flex-direction:column;align-items:center;gap:12px;padding:32px 0;\"><svg style=\"width:48px;height:48px;color:#d4d4d4;\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\"/></svg><p style=\"font-size:13px;color:#78716c;\">No preview for <strong>'+ext.toUpperCase()+'</strong> files.</p><a href=\"'+url+'\" target=\"_blank\" style=\"display:inline-flex;align-items:center;gap:6px;padding:8px 20px;border-radius:10px;background:#991b1b;color:#fff;font-size:13px;font-weight:500;text-decoration:none;\">Open File</a></div>';}\n    document.getElementById('filePreviewOverlay').style.display='flex';\n}\nfunction closeFilePreview(){\n    document.getElementById('filePreviewOverlay').style.display='none';\n    var b=document.getElementById('filePreviewBody');b.innerHTML='';b.style.padding='';b.style.minHeight='220px';\n}\ndocument.getElementById('filePreviewOverlay').addEventListener('click',function(e){if(e.target===this)closeFilePreview();});\ndocument.addEventListener('keydown',function(e){if(e.key==='Escape')closeFilePreview();});\n</script>\n@endsection\n";
    }

    // ── Routes ─────────────────────────────────────────────────────────────────

    private function appendRoutes(string $modelName, string $routeSlug): void
    {
        $generatedRoutesFile = base_path('routes/generated.php');
        
        // Create the generated routes file if it doesn't exist
        if (!file_exists($generatedRoutesFile)) {
            $stub = "<?php\n\n"
                . "use Illuminate\\Support\\Facades\\Route;\n\n"
                . "/*\n"
                . "|--------------------------------------------------------------------------\n"
                . "| Generated Routes\n"
                . "|--------------------------------------------------------------------------\n"
                . "|\n"
                . "| This file contains auto-generated routes created by the Page Builder.\n"
                . "| Routes are automatically added when you generate CRUD pages.\n"
                . "|\n"
                . "*/\n\n"
                . "Route::middleware(['auth'])->prefix('generated')->name('generated.')->group(function () {\n"
                . "    // Generated routes will be added here automatically\n"
                . "});\n";
            file_put_contents($generatedRoutesFile, $stub);
            
            // Register the generated routes file in bootstrap/app.php if not already registered
            $this->registerGeneratedRoutesFile();
        }

        $content = file_get_contents($generatedRoutesFile);

        // Add use statement if missing
        $useStatement = "use App\\Http\\Controllers\\Generated\\{$modelName}Controller;";
        if (!str_contains($content, $useStatement)) {
            // Add after the existing use statements
            $content = preg_replace(
                '/(use Illuminate\\\\Support\\\\Facades\\\\Route;)/',
                "$1\n{$useStatement}",
                $content
            );
        }

        // Build the route lines
        $routeLines = "    Route::get('{$routeSlug}/export', [{$modelName}Controller::class, 'export'])->name('{$routeSlug}.export');\n"
            . "    Route::get('{$routeSlug}/export/{exportLog}/download', [{$modelName}Controller::class, 'exportDownload'])->name('{$routeSlug}.export.download');\n"
            . "    Route::resource('{$routeSlug}', {$modelName}Controller::class);\n";

        // Only add if not already present
        if (!str_contains($content, "Route::resource('{$routeSlug}'")) {
            // Insert before the closing }); of the group
            $lastBrace = strrpos($content, '});');
            if ($lastBrace !== false) {
                $content = substr($content, 0, $lastBrace) . "\n{$routeLines}\n" . substr($content, $lastBrace);
            }
        }

        file_put_contents($generatedRoutesFile, $content);
    }

    private function registerGeneratedRoutesFile(): void
    {
        $bootstrapFile = base_path('bootstrap/app.php');
        $content = file_get_contents($bootstrapFile);

        // Check if generated routes are already registered
        if (str_contains($content, "routes/generated.php") || str_contains($content, "'generated.php'")) {
            return;
        }

        // Add the generated routes file registration
        // Look for the web routes registration and add after it
        if (str_contains($content, "->withRouting(")) {
            // Modern Laravel 11 style
            $pattern = '/(->withRouting\([^)]*web:\s*__DIR__\s*\.\s*\'\.\.\/routes\/web\.php\',)/';
            $replacement = "$1\n            then: function () {\n                Route::middleware('web')\n                    ->group(base_path('routes/generated.php'));\n            },";
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            // Fallback: add a comment suggesting manual registration
            $comment = "\n// Note: Register routes/generated.php in your RouteServiceProvider or bootstrap/app.php\n";
            if (!str_contains($content, "Register routes/generated.php")) {
                $content = str_replace("<?php\n", "<?php\n{$comment}", $content);
            }
        }

        file_put_contents($bootstrapFile, $content);
    }
}
