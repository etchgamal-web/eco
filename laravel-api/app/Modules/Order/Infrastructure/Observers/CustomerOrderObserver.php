<?php

namespace App\Modules\Order\Infrastructure\Observers;

use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Order\Infrastructure\Models\OrderActivity;
use App\Modules\Settings\Infrastructure\Models\Setting;

final class CustomerOrderObserver
{
    public function created(CustomerOrder $order): void
    {
        if ($order->order_number === null) {
            $prefix = trim((string) (Setting::query()->where('key', 'store.order_number_prefix')->first()?->getTypedValue() ?? 'ECO'));
            $prefix = preg_replace('/[^A-Za-z0-9_-]+/', '-', $prefix) ?: 'ECO';
            $order->updateQuietly(['order_number' => strtoupper($prefix).'-'.now()->format('Y').'-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)]);
        }
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
