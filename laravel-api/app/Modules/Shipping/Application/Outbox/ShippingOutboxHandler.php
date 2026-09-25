<?php

namespace App\Modules\Shipping\Application\Outbox;

use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Shared\Application\Outbox\OutboxEventHandlerInterface;
use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShipmentOperationRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShipmentRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShippingProviderInterface;
use RuntimeException;

final class ShippingOutboxHandler implements OutboxEventHandlerInterface
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $shipments,
        private readonly ShippingProviderInterface $providers,
        private readonly ShipmentOperationRepositoryInterface $operations,
        private readonly OrderRepositoryInterface $orders,
        private readonly OutboxEventRepositoryInterface $outbox,
    ) {}

    public function supports(string $eventType): bool
    {
        return $eventType === 'shipment.create.requested';
    }

    public function handle(object $event): void
    {
        $shipment = $this->shipments->find((int) $event->aggregate_id);
        if ($shipment->creation_status === 'created' || data_get($shipment->metadata, 'provider_reference')) {
            $this->outbox->markDispatched((string) $event->deduplication_key);

            return;
        }
        if (! $this->providers->supports($shipment)) {
            throw new RuntimeException('The selected shipping provider is unavailable or not configured.');
        }

        $key = (string) $shipment->idempotency_key;
        $previous = $this->operations->successfulResponse((int) $shipment->id, 'create');
        if ($previous !== null) {
            $this->shipments->updateProviderData($shipment, $previous);
            $this->outbox->markDispatched((string) $event->deduplication_key);

            return;
        }
        if ($this->operations->hasAttempted((int) $shipment->id, 'create')) {
            $recovered = $this->providers->recover($shipment);
            if ($recovered === null) {
                throw new RuntimeException('Shipment creation was previously attempted, but the provider could not recover an existing shipment safely.');
            }
            $this->operations->complete((int) $shipment->id, 'create', 'provider_created', data_get($recovered, 'metadata.provider_reference'), $recovered);
            $created = $this->shipments->updateProviderData($shipment, $recovered);
            $this->markOrderShipped($created);
            $this->outbox->markDispatched((string) $event->deduplication_key);

            return;
        }

        $this->shipments->markCreationPending($shipment);
        $this->operations->start((int) $shipment->id, 'create', $key);
        $result = $this->providers->create($shipment);
        $this->operations->complete((int) $shipment->id, 'create', 'provider_created', data_get($result, 'metadata.provider_reference'), $result);
        $created = $this->shipments->updateProviderData($shipment, $result);
        $this->markOrderShipped($created);
        $this->outbox->markDispatched((string) $event->deduplication_key);
    }

    private function markOrderShipped(object $shipment): void
    {
        $order = $this->orders->find((int) $shipment->order_id);
        if ($order->status === 'processing') {
            $this->orders->updateStatus((int) $order->id, 'shipped');
        }
    }

    public function failed(object $event, \Throwable $exception): void
    {
        $shipment = $this->shipments->find((int) $event->aggregate_id);
        $this->shipments->markCreationFailed($shipment, $exception->getMessage());
        $this->outbox->markFailed((string) $event->deduplication_key, $exception->getMessage());
    }
}
