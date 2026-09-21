<?php

namespace App\Modules\Order\Infrastructure\Persistence;

use App\Models\CustomerOrder;
use App\Models\OrderActivity;
use App\Modules\Order\Domain\Contracts\OrderActivityRepositoryInterface;
use App\Modules\Order\Domain\Exceptions\OrderNotFoundException;

final class EloquentOrderActivityRepository implements OrderActivityRepositoryInterface
{
    public function record(int $orderId, string $event, ?object $actor = null, string $source = 'system', ?string $fromStatus = null, ?string $toStatus = null, array $metadata = []): object
    {
        $order = CustomerOrder::query()->find($orderId);
        if ($order === null) {
            throw new OrderNotFoundException('Order not found.');
        }

        return OrderActivity::query()->create([
            'order_id' => $orderId,
            'event' => $event,
            'actor_id' => $actor?->id,
            'source' => $source,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'metadata' => $metadata === [] ? null : $metadata,
            'occurred_at' => now(),
        ])->load('actor');
    }

    public function listForOrder(int $orderId): iterable
    {
        if (! CustomerOrder::query()->whereKey($orderId)->exists()) {
            throw new OrderNotFoundException('Order not found.');
        }

        return OrderActivity::query()
            ->with('actor')
            ->where('order_id', $orderId)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();
    }
}
