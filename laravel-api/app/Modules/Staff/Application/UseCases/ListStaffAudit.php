<?php

namespace App\Modules\Staff\Application\UseCases;

use App\Modules\Staff\Domain\Contracts\AuditLogRepositoryInterface;

final class ListStaffAudit
{
    public function __construct(private readonly AuditLogRepositoryInterface $audit) {}

    /** @return array{items: array<int, mixed>, meta: array<string, int>} */
    public function execute(int $staffId, array $filters): array
    {
        return $this->audit->listForStaff($staffId, $filters);
    }
}
