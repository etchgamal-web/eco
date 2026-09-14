<?php

namespace App\Modules\Auth\Infrastructure\Persistence;

use App\Models\User;
use App\Modules\Auth\Domain\Contracts\PermissionRepositoryInterface;

final class EloquentPermissionRepository implements PermissionRepositoryInterface
{
    public function userHasPermission(object $user, string $permission): bool
    {
        $override = $user->permissionOverrides()
            ->where('slug', $permission)
            ->first();

        if ($override !== null) {
            return (bool) $override->pivot->allowed;
        }

        return $user->roles()
            ->where('is_active', true)
            ->whereHas('permissions', static function ($query) use ($permission): void {
                $query->where('slug', $permission);
            })
            ->exists();
    }
}
