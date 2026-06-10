<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Modules\Core\Enums\Permission;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, HasUuids;

    protected $table = 'users';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'profile_photo_path',
        'is_active',
        'mfa_secret',
        'mfa_enabled',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'mfa_secret',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'mfa_enabled' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'tenant_id' => $this->tenant_id,
        ];
    }

    /**
     * Check whether this user has at least one permission in the given module.
     * e.g. $user->hasModuleAccess('pos') returns true if the user has any pos.* permission.
     */
    public function hasModuleAccess(string $module): bool
    {
        $permissions = Permission::module($module);

        if (empty($permissions)) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($this->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
