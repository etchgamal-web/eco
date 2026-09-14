<?php

namespace App\Modules\Auth\Domain\Contracts;

interface PermissionRepositoryInterface
{
    public function userHasPermission(object $user, string $permission): bool;
}
