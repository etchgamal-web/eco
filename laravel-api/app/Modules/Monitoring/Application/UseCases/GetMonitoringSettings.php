<?php

namespace App\Modules\Monitoring\Application\UseCases;

use App\Modules\Monitoring\Domain\Contracts\MonitoringRepositoryInterface;

final class GetMonitoringSettings
{
    public function __construct(private readonly MonitoringRepositoryInterface $repo) {}

    public function execute(): array
    {
        return $this->repo->settings();
    }
}
