<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    protected $table = 'master_ledger';
    protected $primaryKey = 'ledger_id';

    protected $fillable = [
        'ledger_name',
        'ledger_code',
        'ledger_head',
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
        return 'ledger_id';
    }
}
