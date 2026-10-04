<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\RbacRepositoryInterface;

final class GetRbacMatrix
{
    public function __construct(private readonly RbacRepositoryInterface $rbac) {}

    /** @return array<string, mixed> */
    public function execute(): array
    {
        return ['roles' => $this->rbac->listRoles(), 'permission_groups' => $this->rbac->listPermissions()];
    }
}
