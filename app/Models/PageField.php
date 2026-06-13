<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageField extends Model
{
    protected $fillable = [
        'page_id', 'field_name', 'field_key', 'field_type', 'sort_order', 'col_span',
        'label', 'column_name', 'placeholder', 'default_value',
        'is_required', 'is_unique', 'is_nullable',
        'column_length', 'description', 'repeater_columns', 'options',
        'formula', 'visibility_rules', 'validation_rules', 'auto_fill',
        'summary_config', 'tax_config',
    ];

    protected $casts = [
        'is_required'       => 'boolean',
        'is_unique'         => 'boolean',
        'is_nullable'       => 'boolean',
        'repeater_columns'  => 'array',
        'options'           => 'array',
        'formula'           => 'array',
        'visibility_rules'  => 'array',
        'validation_rules'  => 'array',
        'auto_fill'         => 'array',
        'summary_config'    => 'array',
        'tax_config'        => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
