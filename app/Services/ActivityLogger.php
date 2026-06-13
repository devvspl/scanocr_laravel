<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogger
{
    /**
     * Log a trail entry for any Eloquent model action.
     *
     * @param  string  $action      created | updated | deleted
     * @param  Model   $subject     The model being acted on
     * @param  array|null $oldValues  Attributes before the change
     * @param  array|null $newValues  Attributes after the change
     */
    public static function log(
        string $action,
        Model $subject,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        /** @var Request $request */
        $request = app(Request::class);

        // Strip hidden / sensitive fields
        $hidden = ['password', 'remember_token', 'created_by'];
        $clean  = fn(?array $v) => $v ? array_diff_key($v, array_flip($hidden)) : null;

        // For updates, only store what actually changed
        if ($action === 'updated' && $oldValues && $newValues) {
            // Flatten any nested arrays to JSON strings for safe scalar comparison
            $flatten = fn(?array $v) => $v ? array_map(
                fn($val) => is_array($val) ? json_encode($val) : $val,
                $v
            ) : null;

            $changed   = array_keys(array_diff_assoc($flatten($newValues), $flatten($oldValues)));
            $oldValues = array_intersect_key($clean($oldValues), array_flip($changed));
            $newValues = array_intersect_key($clean($newValues), array_flip($changed));
        }

        ActivityLog::create([
            'user_id'       => auth()->id(),
            'subject_type'  => get_class($subject),
            'subject_id'    => $subject->getKey(),
            'subject_label' => static::label($subject),
            'action'        => $action,
            'old_values'    => $clean($oldValues),
            'new_values'    => $clean($newValues),
            'ip_address'    => $request->ip(),
            'user_agent'    => substr($request->userAgent() ?? '', 0, 255),
        ]);
    }

    /** Derive a human-readable label from the model. */
    private static function label(Model $model): string
    {
        foreach (['name', 'title', 'label', 'code', 'email'] as $field) {
            $value = $model->{$field} ?? null;
            if (!empty($value) && is_scalar($value)) {
                return (string) $value;
            }
        }
        return class_basename($model) . ' #' . $model->getKey();
    }
}
