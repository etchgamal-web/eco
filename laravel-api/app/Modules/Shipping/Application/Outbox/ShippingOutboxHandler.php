<?php

namespace App\Modules\Shipping\Application\Outbox;

use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Shared\Domain\Contracts\TransactionManagerInterface;
use App\Modules\Shipping\Domain\Contracts\ShipmentOperationRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShipmentRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShippingProviderInterface;
use App\Shared\Domain\Contracts\OutboxEventHandlerInterface;
use App\Shared\Domain\Contracts\OutboxRepositoryInterface;
use App\Shared\Domain\Exceptions\AmbiguousExternalResultException;
use RuntimeException;

final class ShippingOutboxHandler implements OutboxEventHandlerInterface
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $shipments,
        private readonly ShippingProviderInterface $providers,
        private readonly ShipmentOperationRepositoryInterface $operations,
        private readonly OrderRepositoryInterface $orders,
        private readonly OutboxRepositoryInterface $outbox,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    public function supports(string $eventType): bool
    {
        return $eventType === 'shipment.create.requested';
    }

    public function handle(object $event): void
    {
        $shipment = $this->shipments->find((int) $event->aggregate_id);
        if ($shipment->creation_status === 'created' || data_get($shipment->metadata, 'provider_reference')) {
            if (! $this->outbox->ownsClaim((int) $event->id, (string) $event->claim_token)) {
                return;
            }
            $this->markOrderShipped($shipment);
            $this->outbox->markProcessed((int) $event->id, (string) $event->claim_token);

            return;
        }
        if (! $this->providers->supports($shipment)) {
            throw new RuntimeException('The selected shipping provider is unavailable or not configured.');
        }

        $key = (string) $shipment->idempotency_key;
        $operationToken = (string) $event->claim_token;
        $this->operations->start((int) $shipment->id, 'create', $key);
        if (! $this->operations->acquireLease((int) $shipment->id, 'create', $operationToken) || ! $this->outbox->ownsClaim((int) $event->id, $operationToken)) {
            return;
        }
        $previous = $this->operations->successfulResponse((int) $shipment->id, 'create');
        if ($previous !== null) {
            if (! $this->outbox->ownsClaim((int) $event->id, $operationToken)) {
                return;
            }
            $this->completeProviderCreation($shipment, $previous, $operationToken);
            $this->operations->releaseLease((int) $shipment->id, 'create', $operationToken);
            $this->outbox->markProcessed((int) $event->id, (string) $event->claim_token);

            return;
        }
        if ($this->operations->hasAttempted((int) $shipment->id, 'create')) {
            $recovered = $this->providers->recover($shipment);
            if ($recovered === null) {
                throw new RuntimeException('Shipment creation was previously attempted, but the provider could not recover an existing shipment safely.');
            }
            if (! $this->operations->ownsLease((int) $shipment->id, 'create', $operationToken) || ! $this->outbox->ownsClaim((int) $event->id, $operationToken)) {
                return;
            }
            $created = $this->completeProviderCreation($shipment, $recovered, $operationToken);
            $this->operations->complete((int) $shipment->id, 'create', 'provider_created', data_get($recovered, 'metadata.provider_reference'), $recovered, $operationToken);
            $this->outbox->markProcessed((int) $event->id, (string) $event->claim_token);

            return;
        }

        $this->shipments->markCreationPending($shipment);
        $result = $this->providers->create($shipment);
        if (! $this->operations->ownsLease((int) $shipment->id, 'create', $operationToken) || ! $this->outbox->ownsClaim((int) $event->id, $operationToken)) {
            return;
        }
        $created = $this->completeProviderCreation($shipment, $result, $operationToken);
        $this->operations->complete((int) $shipment->id, 'create', 'provider_created', data_get($result, 'metadata.provider_reference'), $result, $operationToken);
        $this->outbox->markProcessed((int) $event->id, (string) $event->claim_token);
    }

    private function markOrderShipped(object $shipment): void
    {
        $order = $this->orders->find((int) $shipment->order_id);
        if ($order->status === 'processing') {
            $this->orders->updateStatus((int) $order->id, 'shipped');
        }
    }

    private function completeProviderCreation(object $shipment, array $data, string $operationToken): object
    {
        return $this->transactions->run(function () use ($shipment, $data, $operationToken): object {
            $created = $this->shipments->updateProviderData($shipment, $data, $operationToken);
            $this->markOrderShipped($created);

            return $created;
        });
    }

    public function failed(object $event, \Throwable $exception): void
    {
        $shipment = $this->shipments->find((int) $event->aggregate_id);
        if (! $this->operations->ownsLease((int) $event->aggregate_id, 'create', (string) $event->claim_token)) {
            return;
        }
        if ($exception instanceof AmbiguousExternalResultException) {
            $this->shipments->updateStatus($shipment, 'ambiguous', null, $exception->getMessage(), (string) $event->claim_token);
            $this->operations->failAmbiguous((int) $event->aggregate_id, 'create', $exception->getMessage(), (string) $event->claim_token);
            $this->outbox->markAmbiguous((int) $event->id, (string) $event->claim_token, $exception->getMessage());

            return;
        }
        $this->shipments->markCreationFailed($shipment, $exception->getMessage());
        $this->operations->fail((int) $event->aggregate_id, 'create', $exception->getMessage(), (string) $event->claim_token);
        $this->outbox->markFailed((int) $event->id, (string) $event->claim_token, $exception->getMessage());
    }
}
