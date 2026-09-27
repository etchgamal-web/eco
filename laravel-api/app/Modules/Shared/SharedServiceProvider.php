<?php

namespace App\Modules\Shared;

use App\Modules\Shared\Application\Health\ReadinessChecker;
use App\Shared\Infrastructure\Outbox\Processing\OutboxProcessor;
use App\Shared\Domain\Contracts\OutboxEventHandlerInterface;
use App\Shared\Infrastructure\Outbox\Contracts\OutboxRepositoryInterface;
use App\Shared\Domain\Contracts\OutboxRepositoryInterface as DomainOutboxRepositoryInterface;
use App\Modules\Shared\Infrastructure\Health\CacheHealthCheck;
use App\Modules\Shared\Infrastructure\Health\DatabaseHealthCheck;
use App\Modules\Shared\Infrastructure\Health\QueueHealthCheck;
use App\Modules\Shared\Infrastructure\Health\StorageHealthCheck;
use App\Shared\Infrastructure\Outbox\Persistence\EloquentOutboxRepository;
use Illuminate\Support\ServiceProvider;

final class SharedServiceProvider extends ServiceProvider
{
    public array $bindings = [
        OutboxRepositoryInterface::class => EloquentOutboxRepository::class,
        DomainOutboxRepositoryInterface::class => EloquentOutboxRepository::class,
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

        $this->app->when(OutboxProcessor::class)
            ->needs('$handlers')
            ->giveTagged(OutboxEventHandlerInterface::class);
    }
}
