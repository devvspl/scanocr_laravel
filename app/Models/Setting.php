<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = [
        'user_id',
        'app_name',
        'timezone',
        'date_format',
        'theme_color',
        'dense_view',
    ];

    protected $casts = [
        'dense_view' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Fetch (or create) the settings row for the given user.
     */
    public static function forUser(int $userId): static
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'app_name'    => 'WolfBooks',
                'timezone'    => 'UTC',
                'date_format' => 'DD/MM/YYYY',
                'theme_color' => 'wolf_red',
                'dense_view'  => false,
            ]
        );
    }
}
