<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountGroup extends Model
{
    protected $fillable = ['name', 'nature', 'parent_id', 'is_active', 'created_by'];

    protected $casts = ['is_active' => 'boolean'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AccountGroup::class, 'parent_id')->with('children');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public static function tree(): \Illuminate\Database\Eloquent\Collection
    {
        return static::with('children')->whereNull('parent_id')->orderBy('name')->get();
    }
}
