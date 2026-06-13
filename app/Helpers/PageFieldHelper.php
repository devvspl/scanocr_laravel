<?php

namespace App\Helpers;

use App\Models\PageField;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageFieldHelper
{
    /**
     * Get the database column name for a field.
     */
    public static function columnName(PageField $field): string
    {
        if ($field->column_name) {
            return preg_replace('/[^a-z0-9_]/', '', strtolower($field->column_name));
        }
        return Str::snake(preg_replace('/[^a-zA-Z0-9\s]/', '', $field->field_name));
    }

    /**
     * Get the HTML input name attribute for a field.
     */
    public static function inputName(PageField $field): string
    {
        return $field->column_name ?: Str::snake(preg_replace('/[^a-zA-Z0-9\s]/', '', $field->field_name));
    }

    /**
     * Build Laravel validation rules for a collection of page fields.
     *
     * @param  Collection|array  $fields      Collection of PageField models
     * @param  string|null       $tableName   Table name (for unique rules)
     * @param  int|null          $ignoreId    Record ID to ignore for unique rules (update scenario)
     * @return array  ['column_name' => ['rule1', 'rule2', ...], ...]
     */
    public static function validationRules($fields, ?string $tableName = null, ?int $ignoreId = null): array
    {
        $rules = [];

        foreach ($fields as $field) {
            if ($field->field_type === 'repeater') {
                continue; // Repeaters are handled separately
            }

            $col = self::columnName($field);
            $isFileField = in_array($field->field_type, ['image', 'file']);
            $fieldRules = [];

            // Required / nullable
            if ($field->is_required) {
                if ($isFileField) {
                    // File fields are validated client-side and handled separately in controllers
                    // Skip adding to validation rules since they come as arrays via FormData
                    continue;
                }
                $fieldRules[] = 'required';
            } else {
                if ($isFileField) {
                    continue;
                }
                $fieldRules[] = 'nullable';
            }

            // Type-specific rules
            switch ($field->field_type) {
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'url':
                    $fieldRules[] = 'url';
                    break;
                case 'number':
                    $fieldRules[] = 'integer';
                    break;
                case 'decimal':
                case 'currency':
                    $fieldRules[] = 'numeric';
                    break;
                case 'toggle':
                case 'checkbox':
                    $fieldRules[] = 'boolean';
                    break;
                case 'date':
                case 'datetime':
                    $fieldRules[] = 'date';
                    break;
                case 'time':
                    $fieldRules[] = 'date_format:H:i';
                    break;
                case 'rating':
                    $fieldRules[] = 'integer';
                    $fieldRules[] = 'min:1';
                    $fieldRules[] = 'max:5';
                    break;
                case 'image':
                case 'file':
                    $fieldRules[] = 'array';
                    break;
                case 'json':
                    $fieldRules[] = 'json';
                    break;
                case 'color':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'regex:/^#[0-9A-Fa-f]{6}$/';
                    break;
                case 'phone':
                    $fieldRules[] = 'string';
                    break;
                default:
                    // title, content, slug, password, etc.
                    $fieldRules[] = 'string';
                    break;
            }

            // Max length
            if ($field->column_length && !$isFileField) {
                $fieldRules[] = 'max:' . $field->column_length;
            }

            // Unique constraint
            if ($field->is_unique && $tableName) {
                if ($ignoreId) {
                    $fieldRules[] = Rule::unique($tableName, $col)->ignore($ignoreId);
                } else {
                    $fieldRules[] = Rule::unique($tableName, $col);
                }
            }

            $rules[$col] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Get select/radio options resolved from field configuration.
     * Handles dynamic (database) and static options.
     *
     * @return array  [['value' => ..., 'label' => ...], ...]
     */
    public static function resolveOptions(PageField $field): array
    {
        $fieldOptions = $field->options ?? [];

        // Dynamic options from database table
        if (!empty($fieldOptions['dynamic']['enabled']) && !empty($fieldOptions['dynamic']['table'])) {
            $dyn = $fieldOptions['dynamic'];
            $table = $dyn['table'];

            if (!Schema::hasTable($table)) {
                return [];
            }

            $labelCol = $dyn['label_col'] ?? 'name';
            $valueCol = $dyn['value_col'] ?? 'id';

            return DB::table($table)
                ->select($valueCol, $labelCol)
                ->get()
                ->map(fn($r) => ['value' => $r->$valueCol, 'label' => $r->$labelCol])
                ->toArray();
        }

        // Static options (new format)
        if (!empty($fieldOptions['static'])) {
            return $fieldOptions['static'];
        }

        // Legacy format: plain array of options
        if (is_array($fieldOptions) && !isset($fieldOptions['static']) && !isset($fieldOptions['dynamic'])) {
            return $fieldOptions;
        }

        return [];
    }

    /**
     * Get the default value for a field (handles use_current_date, etc.)
     *
     * @param  mixed  $existingValue  Existing value (for edit mode)
     */
    public static function defaultValue(PageField $field, $existingValue = null): mixed
    {
        if ($existingValue !== null && $existingValue !== '') {
            return $existingValue;
        }

        $options = $field->options ?? [];

        // Date/time fields with "use current date" option
        if (!empty($options['use_current_date'])) {
            return match ($field->field_type) {
                'date'     => date('Y-m-d'),
                'datetime' => date('Y-m-d\TH:i'),
                'time'     => date('H:i'),
                default    => $field->default_value ?? '',
            };
        }

        return $field->default_value ?? '';
    }

    /**
     * Get the HTML input type for a field type.
     */
    public static function htmlInputType(string $fieldType): string
    {
        return match ($fieldType) {
            'number', 'rating', 'currency', 'decimal' => 'number',
            'email'    => 'email',
            'phone'    => 'tel',
            'url'      => 'url',
            'password' => 'password',
            'date'     => 'date',
            'datetime' => 'datetime-local',
            'time'     => 'time',
            'color'    => 'color',
            default    => 'text',
        };
    }

    /**
     * Get the col-span CSS class for a field.
     */
    public static function colSpanClass(PageField $field, bool $useGrid = true): string
    {
        if (!$useGrid) {
            return '';
        }

        if (in_array($field->field_type, ['file', 'image', 'repeater'])) {
            return 'col-span-3';
        }

        return 'col-span-' . ($field->col_span ?? 3);
    }

    /**
     * Get step attribute value for number inputs.
     */
    public static function stepValue(string $fieldType): string
    {
        return match ($fieldType) {
            'number', 'rating' => '1',
            'decimal', 'currency' => '0.01',
            default => 'any',
        };
    }
}
