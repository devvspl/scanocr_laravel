<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdometerReading extends Model
{
    protected $table = 'odometer_readings';

    protected $fillable = [
        'original_filename', 'stored_filename', 'file_extension',
        'odometer_type', 'source_type', 'raw_ocr_text',
        'reading_value', 'reading_unit', 'ocr_confidence',
        'extraction_confidence', 'is_valid_range', 'validation_message',
        'bounding_box', 'cropped_filename',
        'confirmed_reading', 'confirmed_unit', 'status',
        'added_to_training', 'user_remark', 'created_by',
    ];

    protected $casts = [
        'bounding_box'      => 'array',
        'is_valid_range'    => 'boolean',
        'added_to_training' => 'boolean',
        'reading_value'     => 'decimal:1',
        'confirmed_reading' => 'decimal:1',
        'ocr_confidence'    => 'decimal:2',
        'extraction_confidence' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFileUrlAttribute(): string
    {
        return asset('storage/odometer/' . $this->stored_filename);
    }

    public function getCropUrlAttribute(): ?string
    {
        if (!$this->cropped_filename) return null;
        return asset('storage/odometer/crops/' . $this->cropped_filename);
    }
}
