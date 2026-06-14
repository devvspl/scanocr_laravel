<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table      = 'master_work_location';
    protected $primaryKey = 'location_id';

    protected $fillable = [
        'location_name',
        'location_code',
        'status',
        'created_by',
        'updated_by',
        'is_deleted',
    ];

    /**
     * Scope: only active (status = 'A') and not soft-deleted.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'A')->where('is_deleted', 'N');
    }
}
