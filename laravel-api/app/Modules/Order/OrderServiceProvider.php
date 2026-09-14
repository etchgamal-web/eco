<?php

namespace App\Modules\Order;

use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Domain\Contracts\ReturnRepositoryInterface;
use App\Modules\Order\Domain\Contracts\TransactionManagerInterface;
use App\Modules\Order\Infrastructure\Persistence\DatabaseTransactionManager;
use App\Modules\Order\Infrastructure\Persistence\EloquentOrderRepository;
use App\Modules\Order\Infrastructure\Persistence\EloquentReturnRepository;
use Illuminate\Support\ServiceProvider;

final class OrderServiceProvider extends ServiceProvider
{
    public array $bindings = [
        OrderRepositoryInterface::class => EloquentOrderRepository::class,
        ReturnRepositoryInterface::class => EloquentReturnRepository::class,
        TransactionManagerInterface::class => DatabaseTransactionManager::class,
    ];
}
