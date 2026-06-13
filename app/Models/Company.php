<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name', 'legal_name', 'display_name', 'code', 'type', 'industry',
        'website', 'email', 'phone', 'mobile', 'fax', 'description', 'logo',
        'address_line1', 'address_line2', 'city', 'state', 'country', 'pincode',
        'gstin', 'pan', 'tan', 'cin', 'msme_number', 'gst_registration_type', 'gst_registration_date',
        'bank_name', 'bank_branch', 'bank_account_number', 'bank_ifsc', 'bank_swift', 'bank_account_type',
        'fy_start_month', 'currency_code', 'currency_symbol', 'date_format', 'timezone',
        'is_active', 'is_default', 'created_by',
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'is_default'            => 'boolean',
        'gst_registration_date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function getDefault(): ?static
    {
        return static::where('is_default', true)->where('is_active', true)->first()
            ?? static::where('is_active', true)->first();
    }
}
