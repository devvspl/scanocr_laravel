<?php

namespace App\Services;

use App\Models\Company;
use App\Models\UserCompanyAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class UserAccessService
{
    /**
     * Cache TTL — 6 hours. Busted explicitly on any access change.
     */
    private const TTL = 21600;

    // ── Cache key helpers ─────────────────────────────────────────────────────

    public static function companyKey(int $userId): string
    {
        return "user_company_access:{$userId}";
    }

    // ── Getters (cached) ──────────────────────────────────────────────────────

    /**
     * Returns the Collection of companies the user can see.
     * Super Admin → all active companies.
     * Others with no explicit rows → all active companies (default open).
     * Others with rows → only rows where has_access = true.
     */
    public static function allowedCompanies(int $userId, bool $isSuperAdmin = false): Collection
    {
        return Cache::remember(static::companyKey($userId), static::TTL, function () use ($userId, $isSuperAdmin) {
            $all = Company::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'is_default']);

            if ($isSuperAdmin) {
                return $all;
            }

            $rows = UserCompanyAccess::where('user_id', $userId)->get()->keyBy('company_id');

            if ($rows->isEmpty()) {
                // No explicit settings → default open
                return $all;
            }

            $allowedIds = $rows->filter(fn($r) => $r->has_access)->keys()->toArray();

            return $all->whereIn('id', $allowedIds)->values();
        });
    }

    // ── Cache busting ─────────────────────────────────────────────────────────

    public static function forgetCompanyCache(int $userId): void
    {
        Cache::forget(static::companyKey($userId));
    }

    /**
     * Forget all user caches — call after role changes that may grant Super Admin.
     */
    public static function forgetAll(int $userId): void
    {
        static::forgetCompanyCache($userId);
    }
}
