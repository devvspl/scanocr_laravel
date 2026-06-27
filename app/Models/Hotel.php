<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    protected $table = 'master_hotel';
    protected $primaryKey = 'hotel_id';

    protected $fillable = [
        'hotel_name',
        'state_id',
        'address',
        'city_name',
        'status',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'A')->where('is_deleted', 'N');
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', 'N');
    }

    public function getRouteKeyName()
    {
        return 'hotel_id';
    }
}
