<?php

namespace App\Modules\Order;

use App\Modules\Order\Domain\Contracts\CheckoutGatewayInterface;
use App\Modules\Order\Domain\Contracts\CheckoutPolicyInterface;
use App\Modules\Order\Domain\Contracts\OrderActivityRepositoryInterface;
use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Domain\Contracts\OrderReviewRepositoryInterface;
use App\Modules\Order\Domain\Contracts\ReturnRepositoryInterface;
use App\Modules\Order\Domain\Contracts\TransactionManagerInterface;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Order\Infrastructure\Models\OrderReview;
use App\Modules\Order\Infrastructure\Observers\CustomerOrderObserver;
use App\Modules\Order\Infrastructure\Observers\OrderReviewObserver;
use App\Modules\Order\Infrastructure\Persistence\DatabaseTransactionManager;
use App\Modules\Order\Infrastructure\Persistence\EloquentCheckoutGateway;
use App\Modules\Order\Infrastructure\Persistence\EloquentCheckoutPolicy;
use App\Modules\Order\Infrastructure\Persistence\EloquentOrderActivityRepository;
use App\Modules\Order\Infrastructure\Persistence\EloquentOrderRepository;
use App\Modules\Order\Infrastructure\Persistence\EloquentOrderReviewRepository;
use App\Modules\Order\Infrastructure\Persistence\EloquentReturnRepository;
use App\Modules\Shared\Domain\Contracts\TransactionManagerInterface as SharedTransactionManagerInterface;
use Illuminate\Support\ServiceProvider;

final class OrderServiceProvider extends ServiceProvider
{
    public array $bindings = [
        CheckoutPolicyInterface::class => EloquentCheckoutPolicy::class,
        CheckoutGatewayInterface::class => EloquentCheckoutGateway::class,
        OrderActivityRepositoryInterface::class => EloquentOrderActivityRepository::class,
        OrderRepositoryInterface::class => EloquentOrderRepository::class,
        OrderReviewRepositoryInterface::class => EloquentOrderReviewRepository::class,
        ReturnRepositoryInterface::class => EloquentReturnRepository::class,
        TransactionManagerInterface::class => DatabaseTransactionManager::class,
        SharedTransactionManagerInterface::class => DatabaseTransactionManager::class,
    ];

    public function boot(): void
    {
        CustomerOrder::observe(CustomerOrderObserver::class);
        OrderReview::observe(OrderReviewObserver::class);
    }
}
