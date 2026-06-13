<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NumberingSetting extends Model
{
    protected $fillable = [
        'company_id', 'document_type', 'prefix', 'suffix',
        'next_number', 'pad_length', 'reset_frequency',
        'include_date', 'date_format', 'separator', 'preview', 'created_by',
    ];

    protected $casts = [
        'include_date' => 'boolean',
        'next_number'  => 'integer',
        'pad_length'   => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function buildPreview(): string
    {
        $num  = str_pad($this->next_number, $this->pad_length, '0', STR_PAD_LEFT);
        $sep  = $this->separator ?: '/';
        $parts = array_filter([$this->prefix]);

        if ($this->include_date) {
            $parts[] = now()->format(
                str_replace(['YYYY', 'MM', 'DD'], ['Y', 'm', 'd'], $this->date_format)
            );
        }

        $parts[] = $num;

        if ($this->suffix) {
            $parts[] = $this->suffix;
        }

        return implode($sep, $parts);
    }

    // Default numbering settings for all document types — now sourced from DB via DocumentType model
    // This is kept for seeding/migration purposes only
    public static function defaults(): array
    {
        return \App\Models\DocumentType::orderBy('sort_order')
            ->where('is_active', true)
            ->get(['key as document_type', 'label', 'default_prefix as prefix', 'icon_path as icon'])
            ->toArray();
    }
}
