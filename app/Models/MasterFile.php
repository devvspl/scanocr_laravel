<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterFile extends Model
{
    protected $table = 'master_file';
    protected $primaryKey = 'file_id';

    protected $fillable = [
        'file_name',
        'file_code',
        'company_id',
        'status',
        'created_by',
        'updated_by',
        'is_deleted',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
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
        return 'file_id';
    }
}
