<?php

namespace App\Modules\Shared;

use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;
use App\Modules\Shared\Infrastructure\Persistence\EloquentOutboxEventRepository;
use Illuminate\Support\ServiceProvider;

final class SharedServiceProvider extends ServiceProvider
{
    public array $bindings = [
        OutboxEventRepositoryInterface::class => EloquentOutboxEventRepository::class,
    ];
}
