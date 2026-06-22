<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterDoctype extends Model
{
    protected $table = 'master_doctype';
    protected $primaryKey = 'type_id';
    public $timestamps = false;

    protected $fillable = [
        'file_type',
        'alias',
        'status',
        'is_punch',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'A');
    }
}
