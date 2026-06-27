<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ScanActionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'scan_id',
        'action',
        'action_label',
        'performed_by',
        'performer_name',
        'remark',
        'metadata',
        'performed_at',
    ];

    protected $casts = [
        'metadata'     => 'array',
        'performed_at' => 'datetime',
    ];

    /**
     * Quick helper to log a scan action.
     */
    public static function log(int $scanId, string $action, string $label, ?string $remark = null, ?array $metadata = null): self
    {
        $user = Auth::user();

        return static::create([
            'scan_id'        => $scanId,
            'action'         => $action,
            'action_label'   => $label,
            'performed_by'   => $user?->id,
            'performer_name' => $user?->name ?? 'System',
            'remark'         => $remark,
            'metadata'       => $metadata,
            'performed_at'   => now(),
        ]);
    }

    /**
     * Get all logs for a scan_id in chronological order.
     */
    public static function forScan(int $scanId)
    {
        return static::where('scan_id', $scanId)
            ->orderBy('performed_at')
            ->get();
    }
}
