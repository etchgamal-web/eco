<?php

namespace App\Modules\Monitoring\Application\UseCases;

use App\Modules\Monitoring\Domain\Contracts\MonitoringRepositoryInterface;

final class GetDelayedOrders
{
    public function __construct(private readonly MonitoringRepositoryInterface $repo) {}

    public function execute(array $filters): array
    {
        return $this->repo->delayed($filters);
    }
}
