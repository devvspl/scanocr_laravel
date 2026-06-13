<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Account extends Model
{
    protected $fillable = [
        'code', 'name', 'account_group_id',
        'opening_balance', 'balance_type', 'is_active', 'description', 'created_by',
    ];

    protected $casts = ['is_active' => 'boolean', 'opening_balance' => 'decimal:2'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class, 'account_group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
