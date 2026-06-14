<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'parent_id',
        'is_active',
        'phone',
        'designation',
        'department',
        'location_id',
        'signature_path',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the parent user (for sub-users)
     */
    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Get all sub-users
     */
    public function subUsers()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    /**
     * Document type access pivot
     */
    public function documentTypeAccess()
    {
        return $this->hasMany(\App\Models\UserDocumentTypeAccess::class);
    }

    public function allowedDocumentTypeIds(): array
    {
        return $this->documentTypeAccess()->where('can_view', true)->pluck('document_type_id')->toArray();
    }

    /**
     * Company access pivot
     */
    public function companyAccess()
    {
        return $this->hasMany(\App\Models\UserCompanyAccess::class);
    }

    public function allowedCompanyIds(): array
    {
        return $this->companyAccess()->where('has_access', true)->pluck('company_id')->toArray();
    }

    /**
     * Location access pivot
     */
    public function locationAccess()
    {
        return $this->hasMany(\App\Models\UserLocationAccess::class);
    }

    public function allowedLocationIds(): array
    {
        return $this->locationAccess()->where('has_access', true)->pluck('location_id')->toArray();
    }

    /**
     * Get the user who created this user
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if user is a sub-user
     */
    public function isSubUser(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * Check if user is a main user
     */
    public function isMainUser(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Get all users under this user (including sub-users of sub-users)
     */
    public function allSubUsers()
    {
        return $this->subUsers()->with('subUsers');
    }
}
