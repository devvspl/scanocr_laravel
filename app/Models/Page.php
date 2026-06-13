<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    protected $fillable = ['user_id', 'page_name', 'is_generated', 'settings'];

    protected $casts = [
        'is_generated' => 'boolean',
        'settings'     => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(PageField::class)->orderBy('sort_order');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(PageShare::class);
    }

    public function activeShares(): HasMany
    {
        return $this->hasMany(PageShare::class)->where('is_active', true);
    }
}
