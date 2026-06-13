<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WfEmailTemplate extends Model
{
    protected $table = 'wf_email_templates';

    protected $fillable = [
        'name',
        'slug',
        'subject',
        'body_html',
        'category',
        'variables',
        'is_default',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'variables'  => 'array',
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name) . '-' . time();
            }
            $model->created_by = auth()->id();
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
