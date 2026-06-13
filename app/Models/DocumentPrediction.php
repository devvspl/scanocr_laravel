<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentPrediction extends Model
{
    protected $table = 'document_predictions';

    protected $fillable = [
        'original_filename',
        'stored_filename',
        'file_extension',
        'ocr_text',
        'predicted_type_id',
        'confidence_score',
        'predicted_department_id',
        'department_confidence',
        'predicted_location_id',
        'location_confidence',
        'confirmed_type_id',
        'status',
        'user_remark',
        'ocr_page_count',
        'ocr_page_texts',
        'prediction_reasoning',
        'created_by',
    ];

    protected $casts = [
        'ocr_page_texts'        => 'array',
        'prediction_reasoning'   => 'array',
        'confidence_score'       => 'decimal:2',
        'department_confidence'  => 'decimal:2',
        'location_confidence'    => 'decimal:2',
        'created_at'             => 'datetime',
        'updated_at'             => 'datetime',
    ];

    public function predictedType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'predicted_type_id');
    }

    public function confirmedType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'confirmed_type_id');
    }

    public function predictedDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'predicted_department_id');
    }

    public function predictedLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'predicted_location_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get storage URL for file.
     */
    public function getFileUrlAttribute(): string
    {
        return asset('storage/document-ai/' . $this->stored_filename);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }
}
