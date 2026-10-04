<?php

namespace App\Modules\Shared;

use App\Modules\Shared\Application\Health\ReadinessChecker;
use App\Modules\Shared\Infrastructure\Health\CacheHealthCheck;
use App\Modules\Shared\Infrastructure\Health\DatabaseHealthCheck;
use App\Modules\Shared\Infrastructure\Health\QueueHealthCheck;
use App\Modules\Shared\Infrastructure\Health\StorageHealthCheck;
use App\Shared\Domain\Contracts\OutboxEventHandlerInterface;
use App\Shared\Domain\Contracts\OutboxRepositoryInterface as DomainOutboxRepositoryInterface;
use App\Shared\Infrastructure\Outbox\Persistence\EloquentOutboxRepository;
use App\Shared\Infrastructure\Outbox\Processing\OutboxProcessor;
use Illuminate\Support\ServiceProvider;

final class SharedServiceProvider extends ServiceProvider
{
    public array $bindings = [
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
