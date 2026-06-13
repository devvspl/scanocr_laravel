<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * Activity logging has been disabled — the activity_logs table was removed.
     * This stub keeps all callers intact without runtime errors.
     */
    public static function log(
        string $action,
        Model $subject,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        // no-op
    }
}
