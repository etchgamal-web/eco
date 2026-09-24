<?php

namespace App\Modules\Monitoring\Application\UseCases;

use App\Modules\Monitoring\Domain\Contracts\MonitoringRepositoryInterface;

final class ResolveOperationalAlert
{
    public function __construct(private readonly MonitoringRepositoryInterface $repo) {}

    public function execute(int $id, int $userId): mixed
    {
        return $this->repo->resolve($id, $userId);
    }
}
