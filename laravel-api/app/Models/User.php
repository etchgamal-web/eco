<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
class User extends Authenticatable
{
    use HasFactory, Notifiable;

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

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(CustomerCart::class);
    }

    public function wishlist(): HasMany
    {
        return $this->hasMany(CustomerWishlist::class);
    }

    public function preferences(): HasOne
    {
        return $this->hasOne(CustomerPreference::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(CustomerNotification::class);
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
