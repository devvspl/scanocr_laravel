<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLocationAccess extends Model
{
    protected $table = 'user_location_access';

    protected $fillable = ['user_id', 'location_id', 'has_access'];

    protected $casts = ['has_access' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }
}
