<?php

namespace App\Modules\Order\Infrastructure\Persistence;

use App\Modules\Catalog\Domain\Contracts\ProductReaderInterface;
use App\Modules\Promotion\Domain\Contracts\CouponServiceInterface;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Order\Domain\Contracts\CheckoutGatewayInterface;

final class EloquentCheckoutGateway implements CheckoutGatewayInterface
{
    public function __construct(
        private readonly ProductReaderInterface $products,
        private readonly CouponServiceInterface $coupons,
    ) {}

    public function findByIdempotencyKey(string $key): ?object
    {
        return CustomerOrder::query()->where('idempotency_key', $key)->first();
    }

    public function productForGuest(int $productId): ?object
    {
        return $this->products->findForCheckout($productId);
    }

    public function createOrder(array $attributes): object
    {
        return CustomerOrder::query()->create($attributes);
    }

    public function createOrderItems(object $order, array $items): void
    {
        $order->items()->createMany($items);
    }

    public function recordCouponUsage(string $code, int $userId, int $orderId, int $discount): void
    {
        $this->coupons->recordUsage($code, $userId, $orderId, $discount);
    }

}
