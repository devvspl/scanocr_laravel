<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nature extends Model
{
    protected $fillable = ['name', 'slug', 'color', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function accountGroups(): HasMany
    {
        return $this->hasMany(AccountGroup::class, 'nature', 'slug');
    }
}
