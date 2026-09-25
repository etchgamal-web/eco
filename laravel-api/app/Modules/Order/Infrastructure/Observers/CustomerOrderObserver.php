<?php

namespace App\Modules\Order\Infrastructure\Observers;

use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Order\Infrastructure\Models\OrderActivity;

final class CustomerOrderObserver
{
    public function created(CustomerOrder $order): void
    {
        $this->record($order, 'order_created', null, (string) $order->status);
    }

    public function updated(CustomerOrder $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $this->record(
            $order,
            'status_changed',
            (string) $order->getOriginal('status'),
            (string) $order->status,
        );
    }

    private function record(CustomerOrder $order, string $event, ?string $fromStatus, ?string $toStatus): void
    {
        $actor = auth()->user();
        $source = $actor === null ? 'system' : (method_exists($actor, 'hasRole') && $actor->hasRole('customer') ? 'customer' : 'admin');

        OrderActivity::query()->create([
            'order_id' => $order->id,
            'event' => $event,
            'actor_id' => $actor?->id,
            'source' => $source,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'occurred_at' => now(),
        ]);
    }
}
