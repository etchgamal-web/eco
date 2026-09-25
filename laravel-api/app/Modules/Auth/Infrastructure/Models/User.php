<?php

namespace App\Modules\Auth\Infrastructure\Models;

use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'phone',
    'password',
    'status',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable implements CanResetPassword
{
    use CanResetPasswordTrait, HasApiTokens, HasFactory, Notifiable;

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    /**
     * Roles assigned to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_user',
            'user_id',
            'role_id'
        );
    }

    /**
     * Direct permission overrides assigned to the user.
     */
    public function permissionOverrides(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'user_permissions',
            'user_id',
            'permission_id'
        )->withPivot('allowed')
            ->withTimestamps();
    }

    /**
     * Determine whether the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->where('slug', $role)
            ->exists();
    }

    /**
     * Determine whether the user has a specific permission.
     *
     * Direct user overrides take precedence over role permissions.
     */
    public function hasPermission(string $permission): bool
    {
        $override = $this->permissionOverrides()
            ->where('slug', $permission)
            ->first();

        if ($override !== null) {
            return (bool) $override->pivot->allowed;
        }

        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('slug', $permission);
            })
            ->exists();
    }

    /**
     * Determine whether the user's account is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Determine whether the user has verified their email.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Determine whether the user has verified their phone.
     */
    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    /**
     * Get the user's cast definitions.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
