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
        // Per-user session override takes priority (set via company switcher)
        $sessionId = session('selected_company_id');
        if ($sessionId) {
            $company = static::where('id', $sessionId)->where('is_active', true)->first();
            if ($company) return $company;
        }

        // Fall back to the DB-flagged default, then first active
        return static::where('is_default', true)->where('is_active', true)->first()
            ?? static::where('is_active', true)->first();
    }

    /**
     * Set the current company for this user's session only (no DB change).
     */
    public static function setForSession(int $companyId): void
    {
        session(['selected_company_id' => $companyId]);
    }

    /**
     * Get the ID for the current session company (for convenience).
     */
    public static function currentId(): ?int
    {
        return static::getDefault()?->id;
    }
}
