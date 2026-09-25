<?php

namespace App\Modules\Shared;

use App\Modules\Shared\Application\Health\ReadinessChecker;
use App\Modules\Shared\Application\Outbox\OutboxEventDispatcher;
use App\Modules\Shared\Application\Outbox\OutboxEventHandlerInterface;
use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;
use App\Modules\Shared\Infrastructure\Health\CacheHealthCheck;
use App\Modules\Shared\Infrastructure\Health\DatabaseHealthCheck;
use App\Modules\Shared\Infrastructure\Health\QueueHealthCheck;
use App\Modules\Shared\Infrastructure\Health\StorageHealthCheck;
use App\Modules\Shared\Infrastructure\Persistence\EloquentOutboxEventRepository;
use Illuminate\Support\ServiceProvider;

final class SharedServiceProvider extends ServiceProvider
{
    public array $bindings = [
        OutboxEventRepositoryInterface::class => EloquentOutboxEventRepository::class,
    ];

    public function register(): void
    {
        $this->app->when(ReadinessChecker::class)
            ->needs('$checks')
            ->give(static fn (): array => [
                new DatabaseHealthCheck,
                new CacheHealthCheck,
                new StorageHealthCheck,
                new QueueHealthCheck,
            ]);

        $this->app->when(OutboxEventDispatcher::class)
            ->needs('$handlers')
            ->giveTagged(OutboxEventHandlerInterface::class);
    }
}
