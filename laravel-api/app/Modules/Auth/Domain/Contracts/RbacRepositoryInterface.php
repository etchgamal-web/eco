<?php

namespace App\Modules\Auth\Domain\Contracts;

interface RbacRepositoryInterface
{
    /** @return array<int, array<string, mixed>> */
    public function listRoles(): array;

    /** @return array<int, array<string, mixed>> */
    public function listPermissions(): array;

    /** @return array<string, mixed> */
    public function updateRolePermissions(int $roleId, array $permissionSlugs): array;
}
