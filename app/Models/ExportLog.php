<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExportLog extends Model
{
    protected $fillable = ['model', 'file_name', 'file_path', 'row_count', 'data_hash', 'user_id'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
