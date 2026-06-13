<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdometerTrainingData extends Model
{
    protected $table = 'odometer_training_data';

    protected $fillable = [
        'odometer_type', 'source_type', 'true_reading', 'true_unit',
        'ocr_raw_text', 'matched_pattern', 'keywords_found',
        'training_image_filename', 'difficulty_level', 'notes',
        'status', 'created_by',
    ];

    protected $casts = [
        'keywords_found' => 'array',
        'true_reading'   => 'decimal:1',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
