<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'ip_address',
        'user_agent',
        'status',
        'login_at',
        'logout_at',
    ];

    protected $casts = [
        'login_at'  => 'datetime',
        'logout_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Record a successful login for the given user and active company.
     */
    public static function recordLogin(User $user, ?int $companyId, string $ip, string $userAgent): static
    {
        return static::create([
            'user_id'    => $user->id,
            'company_id' => $companyId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'status'     => 'success',
            'login_at'   => now(),
        ]);
    }

    /**
     * Stamp the logout time on the most recent open session for a user.
     */
    public static function recordLogout(int $userId): void
    {
        static::where('user_id', $userId)
            ->whereNull('logout_at')
            ->latest('login_at')
            ->first()
            ?->update(['logout_at' => now()]);
    }
}
