<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterFirm extends Model
{
    use HasFactory;

    protected $table = 'master_firm';
    protected $primaryKey = 'firm_id';

    protected $fillable = [
        'focus_id',
        'focus_data',
        'firm_type',
        'firm_name',
        'firm_code',
        'country_id',
        'state_id',
        'city_name',
        'pin_code',
        'address',
        'gst',
        'status',
        'created_by',
        'updated_by',
        'is_deleted',
        'Import_Flag'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const UPDATED_AT = 'updated_at';
    public const CREATED_AT = 'created_at';

    /**
     * Creator relationship
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Scope for active firms
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'A')
                    ->where('is_deleted', 'N');
    }

    /**
     * Scope for vendors/companies
     */
    public function scopeVendors($query)
    {
        return $query->whereIn('firm_type', ['Company', 'Vendor']);
    }

    /**
     * Get display name for dropdown
     */
    public function getDisplayNameAttribute()
    {
        if ($this->firm_code) {
            return "{$this->firm_name} ({$this->firm_code})";
        }
        return $this->firm_name;
    }

    public function getRouteKeyName()
    {
        return 'firm_id';
    }
}