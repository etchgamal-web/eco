<?php

namespace App\Modules\Staff\Domain\Contracts;

interface AuditLogRepositoryInterface
{
    public function record(?object $actor, string $action, string $targetType, ?int $targetId, array $metadata = []): void;

    /** @return array{items: array<int, mixed>, meta: array<string, int>} */
    public function listForStaff(int $targetId, array $filters): array;

    /** @return array{items: array<int, mixed>, meta: array<string, int>} */
    public function listForRole(int $targetId, array $filters): array;
}
