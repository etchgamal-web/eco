<?php

namespace App\Modules\Integration;

use App\Modules\Integration\Domain\Contracts\IntegrationEventRepositoryInterface;
use App\Modules\Integration\Infrastructure\Persistence\EloquentIntegrationEventRepository;
use Illuminate\Support\ServiceProvider;

final class IntegrationServiceProvider extends ServiceProvider
{
    public array $bindings = [IntegrationEventRepositoryInterface::class => EloquentIntegrationEventRepository::class];
}
