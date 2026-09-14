<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
        'is_active',
    ];

    /**
     * Users assigned to this role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'role_user',
            'role_id',
            'user_id'
        );
    }

    /**
     * Permissions assigned to this role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'permission_role',
            'role_id',
            'permission_id'
        );
    }

    /**
     * Determine whether this is a system role.
     */
    public function isSystem(): bool
    {
        return $this->is_system;
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
