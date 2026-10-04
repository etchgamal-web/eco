<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\RbacRepositoryInterface;

final class UpdateRolePermissions
{
    public function __construct(private readonly RbacRepositoryInterface $rbac) {}

    /** @return array<string, mixed> */
    public function execute(int $roleId, array $permissionSlugs): array
    {
        return $this->rbac->updateRolePermissions($roleId, $permissionSlugs);
    }
}
