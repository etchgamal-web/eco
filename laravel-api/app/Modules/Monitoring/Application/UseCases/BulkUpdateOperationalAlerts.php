<?php

namespace App\Modules\Monitoring\Application\UseCases;

use App\Modules\Monitoring\Domain\Contracts\MonitoringRepositoryInterface;

final class BulkUpdateOperationalAlerts
{
    public function __construct(private readonly MonitoringRepositoryInterface $repository) {}

    public function acknowledge(array $ids, int $userId, ?string $reason = null): array
    {
        return $this->repository->bulkAcknowledge($ids, $userId, $reason);
    }

    public function resolve(array $ids, int $userId, ?string $reason = null): array
    {
        return $this->repository->bulkResolve($ids, $userId, $reason);
    }
}
