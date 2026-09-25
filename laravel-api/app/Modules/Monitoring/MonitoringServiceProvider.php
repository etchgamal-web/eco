<?php

namespace App\Modules\Monitoring;

use App\Modules\Monitoring\Domain\Contracts\MonitoringRepositoryInterface;
use App\Modules\Monitoring\Infrastructure\Persistence\EloquentMonitoringRepository;
use Illuminate\Support\ServiceProvider;

final class MonitoringServiceProvider extends ServiceProvider
{
    public array $bindings = [MonitoringRepositoryInterface::class => EloquentMonitoringRepository::class];
}
