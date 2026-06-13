<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCompanyAccess extends Model
{
    protected $table = 'user_company_access';

    protected $fillable = ['user_id', 'company_id', 'has_access'];

    protected $casts = ['has_access' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
