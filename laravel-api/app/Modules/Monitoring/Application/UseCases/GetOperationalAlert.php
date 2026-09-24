<?php

namespace App\Modules\Monitoring\Application\UseCases;

use App\Modules\Monitoring\Domain\Contracts\MonitoringRepositoryInterface;

final class GetOperationalAlert
{
    public function __construct(private readonly MonitoringRepositoryInterface $repo) {}

    public function execute(int $id): mixed
    {
        return $this->repo->alert($id);
    }
}
