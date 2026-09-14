<?php

namespace App\Modules\Auth\Infrastructure\Authorization;

use App\Modules\Auth\Domain\Contracts\AuthorizationServiceInterface;
use App\Modules\Auth\Domain\Contracts\PermissionRepositoryInterface;
use App\Models\User;

final class LaravelAuthorizationService implements AuthorizationServiceInterface
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissions,
    ) {
    }

    public function allows(object $user, string $permission): bool
    {
        return $this->permissions->userHasPermission($user, $permission);
    }
}
