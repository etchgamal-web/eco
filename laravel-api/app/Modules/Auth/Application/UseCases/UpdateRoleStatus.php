<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\RbacRepositoryInterface;

final class UpdateRoleStatus
{
    public function __construct(private readonly RbacRepositoryInterface $rbac) {}

    /** @return array<string, mixed> */
    public function execute(int $roleId, bool $isActive): array
    {
        return $this->rbac->updateRoleStatus($roleId, $isActive);
    }
}
