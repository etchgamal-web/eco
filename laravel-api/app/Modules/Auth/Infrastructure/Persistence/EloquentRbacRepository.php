<?php

namespace App\Modules\Auth\Infrastructure\Persistence;

use App\Modules\Auth\Domain\Contracts\RbacRepositoryInterface;
use App\Modules\Auth\Domain\Exceptions\AuthorizationException;
use App\Modules\Auth\Infrastructure\Models\Permission;
use App\Modules\Auth\Infrastructure\Models\Role;
use App\Modules\Staff\Infrastructure\Models\AuditLog;
use Illuminate\Support\Facades\DB;

final class EloquentRbacRepository implements RbacRepositoryInterface
{
    public function listRoles(): array
    {
        return Role::query()->with('permissions:id,name,slug,group')->withCount('users')->orderBy('name')->get(['id', 'name', 'slug', 'description', 'is_system', 'is_active'])->map(fn (Role $role) => ['id' => $role->id, 'name' => $role->name, 'slug' => $role->slug, 'description' => $role->description, 'is_system' => $role->is_system, 'is_active' => $role->is_active, 'users_count' => $role->users_count, 'permissions' => $role->permissions->map(fn (Permission $permission) => ['name' => $permission->name, 'slug' => $permission->slug, 'group' => $permission->group])->all()])->all();
    }

    public function listPermissions(): array
    {
        return Permission::query()->orderBy('group')->orderBy('name')->get(['id', 'name', 'slug', 'group', 'description'])->groupBy('group')->map(fn ($permissions, $group) => ['group' => $group, 'permissions' => $permissions->values()->all()])->values()->all();
    }

    public function updateRolePermissions(int $roleId, array $permissionSlugs): array
    {
        $role = Role::query()->findOrFail($roleId);
        if ($role->isSystem() || $role->slug === 'owner') throw new AuthorizationException('System roles cannot be modified.');
        $ids = Permission::query()->whereIn('slug', array_values(array_unique($permissionSlugs)))->pluck('id');
        if ($ids->count() !== count(array_unique($permissionSlugs))) throw new AuthorizationException('One or more permissions are invalid.');
        $before = $role->permissions()->pluck('slug')->sort()->values()->all();
        DB::transaction(fn () => $role->permissions()->sync($ids));
        AuditLog::query()->create([
            'actor_id' => auth()->id(),
            'action' => 'role.permissions_updated',
            'target_type' => Role::class,
            'target_id' => $role->id,
            'metadata' => ['role_slug' => $role->slug, 'before' => $before, 'after' => $permissionSlugs],
        ]);
        return collect($this->listRoles())->firstWhere('id', $role->id) ?? $role->fresh('permissions')->toArray();
    }

    public function updateRoleStatus(int $roleId, bool $isActive): array
    {
        $role = Role::query()->withCount('users')->findOrFail($roleId);
        if ($role->isSystem() || $role->slug === 'owner') {
            throw new AuthorizationException('System roles cannot be deactivated.');
        }
        if (! $isActive && $role->users_count > 0) {
            throw new AuthorizationException('A role with assigned users cannot be deactivated. Reassign the users first.');
        }

        $role->update(['is_active' => $isActive]);
        AuditLog::query()->create([
            'actor_id' => auth()->id(),
            'action' => 'role.status_updated',
            'target_type' => Role::class,
            'target_id' => $role->id,
            'metadata' => ['role_slug' => $role->slug, 'is_active' => $isActive],
        ]);

        return collect($this->listRoles())->firstWhere('id', $role->id) ?? $role->fresh('permissions')->toArray();
    }
}
