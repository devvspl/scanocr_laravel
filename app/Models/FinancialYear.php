<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialYear extends Model
{
    protected $fillable = [
        'label', 'start_date', 'end_date',
        'is_current', 'is_locked', 'notes', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_current' => 'boolean',
        'is_locked'  => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the current financial year for this user's session.
     * Priority: session override → DB is_current flag → first FY ordered by start_date desc
     */
    public static function getCurrent(): ?static
    {
        // Per-user session override takes priority (set via FY switcher)
        $sessionId = session('selected_fy_id');
        if ($sessionId) {
            $fy = static::find($sessionId);
            if ($fy) return $fy;
        }

        // Fall back to the DB-flagged current, then most recent
        return static::where('is_current', true)->first()
            ?? static::orderByDesc('start_date')->first();
    }

    /**
     * Set the current financial year for this user's session only (no DB change).
     */
    public static function setForSession(int $fyId): void
    {
        session(['selected_fy_id' => $fyId]);
    }

    /**
     * Get the ID for the current session FY (for convenience).
     */
    public static function currentId(): ?int
    {
        return static::getCurrent()?->id;
    }
}
