<?php

namespace App\Modules\Order\Infrastructure\Persistence;

use App\Modules\Promotion\Infrastructure\Models\Coupon;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Order\Domain\Contracts\CheckoutGatewayInterface;

final class EloquentCheckoutGateway implements CheckoutGatewayInterface
{
    public function findByIdempotencyKey(string $key): ?object
    {
        return CustomerOrder::query()->where('idempotency_key', $key)->first();
    }

    public function productForGuest(int $productId): ?object
    {
        return Product::query()->with('variants')->find($productId);
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
        $coupon = Coupon::query()->where('code', $code)->firstOrFail();
        $coupon->usages()->create([
            'user_id' => $userId,
            'order_id' => $orderId,
            'discount_amount' => $discount,
        ]);
    }

}
