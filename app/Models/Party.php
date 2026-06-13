<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Party extends Model
{
    protected $fillable = [
        'type', 'code', 'name', 'display_name', 'email', 'phone', 'mobile',
        'gstin', 'pan', 'billing_address', 'shipping_address',
        'city', 'state', 'country', 'pincode',
        'opening_balance', 'balance_type', 'credit_limit', 'credit_days',
        'account_group_id', 'is_active', 'notes', 'created_by',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'opening_balance' => 'decimal:2',
        'credit_days'     => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class, 'account_group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeCustomers($query)
    {
        return $query->where('type', 'customer');
    }

    public function scopeVendors($query)
    {
        return $query->where('type', 'vendor');
    }
}
