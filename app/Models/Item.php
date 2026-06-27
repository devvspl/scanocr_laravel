<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'master_item';
    protected $primaryKey = 'item_id';

    protected $fillable = [
        'item_name',
        'item_code',
        'focus_data',
        'status',
        'is_deleted',
        'created_by',
        'updated_by',
        'Import_Flag',
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
        return 'item_id';
    }
}
