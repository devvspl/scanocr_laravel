<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalLog extends Model
{
    protected $fillable = [
        'document_type',
        'document_id',
        'level',
        'level_name',
        'user_id',
        'action',
        'remarks',
        'signature_path',
        'ip_address',
        'user_agent',
        'signed_at',
        'token',
        'acted_at',
    ];

    protected $casts = [
        'acted_at'  => 'datetime',
        'signed_at' => 'datetime',
        'level'     => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all logs for a specific document.
     */
    public static function forDocument(string $type, int $id)
    {
        return static::where('document_type', $type)
            ->where('document_id', $id)
            ->with('user')
            ->orderBy('level')
            ->orderBy('created_at')
            ->get();
    }
}
