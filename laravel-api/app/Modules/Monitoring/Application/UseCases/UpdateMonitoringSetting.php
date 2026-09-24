<?php

namespace App\Modules\Monitoring\Application\UseCases;

use App\Modules\Monitoring\Domain\Contracts\MonitoringRepositoryInterface;

final class UpdateMonitoringSetting
{
    public function __construct(private readonly MonitoringRepositoryInterface $repo) {}

    public function execute(string $type, int $days, bool $enabled): array
    {
        return $this->repo->saveSetting($type, $days, $enabled);
    }
}
