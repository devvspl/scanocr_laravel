<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $table = 'master_unit';
    protected $primaryKey = 'unit_id';

    protected $fillable = [
        'unit_name',
        'unit_code',
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
        return 'unit_id';
    }
}
