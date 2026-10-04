<?php

namespace App\Modules\Order\Infrastructure\Persistence;

use App\Modules\Order\Domain\Contracts\OrderActivityRepositoryInterface;
use App\Modules\Order\Domain\Exceptions\OrderNotFoundException;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Order\Infrastructure\Models\OrderActivity;

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
        $order = CustomerOrder::query()
            ->with([
                'activities.actor',
                'shipments.events.actor',
                'payments.operations',
                'returns.user',
            ])
            ->find($orderId);
        if ($order === null) {
            throw new OrderNotFoundException('Order not found.');
        }

        $timeline = collect($order->activities->map(fn (OrderActivity $activity): array => [
            ...$activity->toArray(),
            'entity_type' => 'order',
            'entity_id' => $order->id,
            'occurred_at' => $activity->occurred_at,
        ]));

        foreach ($order->shipments as $shipment) {
            foreach ($shipment->events as $event) {
                $timeline->push([
                    'id' => 'shipment-'.$event->id,
                    'event' => 'shipment_status_changed',
                    'source' => 'shipping',
                    'from_status' => $event->from_status,
                    'to_status' => $event->to_status,
                    'note' => $event->note,
                    'actor' => $event->actor,
                    'entity_type' => 'shipment',
                    'entity_id' => $shipment->id,
                    'metadata' => ['provider_code' => $shipment->provider_code, 'tracking_number' => $shipment->tracking_number],
                    'occurred_at' => $event->created_at,
                ]);
            }
        }

        foreach ($order->payments as $payment) {
            foreach ($payment->operations as $operation) {
                $timeline->push([
                    'id' => 'payment-'.$operation->id,
                    'event' => 'payment_operation_'.$operation->operation,
                    'source' => 'payment',
                    'to_status' => $operation->status,
                    'entity_type' => 'payment',
                    'entity_id' => $payment->id,
                    'metadata' => ['requested_amount' => $operation->requested_amount, 'confirmed_amount' => $operation->confirmed_amount, 'provider_reference' => $operation->provider_reference, 'last_error' => $operation->last_error],
                    'occurred_at' => $operation->created_at,
                ]);
            }
        }

        foreach ($order->returns as $return) {
            $timeline->push([
                'id' => 'return-'.$return->id,
                'event' => 'return_status_snapshot',
                'source' => 'returns',
                'to_status' => $return->status,
                'entity_type' => 'return',
                'entity_id' => $return->id,
                'metadata' => ['reason' => $return->reason, 'refund_status' => $return->refund_status, 'restock_status' => $return->restock_status, 'refund_amount' => $return->refund_amount],
                'occurred_at' => $return->updated_at ?? $return->created_at,
            ]);
        }

        return $timeline->sortBy(fn (array $entry): string => (string) ($entry['occurred_at'] ?? ''))->values();
    }
}
