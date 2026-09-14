<?php

namespace App\Modules\Shared\Application\Jobs;

use App\Models\OutboxEvent;
use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Contracts\PaymentOperationRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShipmentOperationRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShipmentRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShippingProviderInterface;
use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessOutboxEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [60, 300, 900, 3600, 21600];

    public function __construct(public readonly int $eventId)
    {
    }

    public function handle(
        PaymentRepositoryInterface $payments,
        PaymentGatewayInterface $gateway,
        PaymentOperationRepositoryInterface $paymentOperations,
        ShipmentRepositoryInterface $shipments,
        ShippingProviderInterface $providers,
        ShipmentOperationRepositoryInterface $shipmentOperations,
        OutboxEventRepositoryInterface $outbox,
    ): void {
        $event = OutboxEvent::query()->find($this->eventId);
        if ($event === null || $event->status === 'dispatched') {
            return;
        }

        try {
            if ($event->aggregate_type === 'payment') {
                $payment = $payments->find((int) $event->aggregate_id);
                if (in_array($payment->status, ['provider_created', 'confirmed', 'paid', 'refunded', 'failed'], true)) {
                    $outbox->markDispatched($event->deduplication_key);
                    return;
                }
                $key = (string) $payment->idempotency_key;
                $previous = $paymentOperations->successfulResponse((int) $payment->id, 'create');
                if ($previous !== null) {
                    $payments->updateStatus($payment, $previous['_operation_status'] ?? 'provider_created', [
                        'provider_reference' => $previous['provider_reference'] ?? null,
                        'metadata' => $previous['metadata'] ?? $payment->metadata,
                    ]);
                    $outbox->markDispatched($event->deduplication_key);
                    return;
                }
                $paymentOperations->start((int) $payment->id, 'create', $key);
                $result = $gateway->createPayment($payment->order, (string) $payment->method, $key);
                $status = ($result['status'] ?? null) === 'paid' ? 'confirmed' : (($result['provider_reference'] ?? null) !== null ? 'provider_created' : 'pending');
                $paymentOperations->complete((int) $payment->id, 'create', $status, $result['provider_reference'] ?? null, $result);
                $payments->updateStatus($payment, $status, ['provider_reference' => $result['provider_reference'] ?? null, 'metadata' => $result['metadata'] ?? $payment->metadata]);
            } elseif ($event->aggregate_type === 'shipment') {
                $shipment = $shipments->find((int) $event->aggregate_id);
                if (data_get($shipment->metadata, 'provider_reference')) {
                    $outbox->markDispatched($event->deduplication_key);
                    return;
                }
                if (! $providers->supports($shipment)) {
                    $outbox->markDispatched($event->deduplication_key);
                    return;
                }
                $key = (string) $shipment->idempotency_key;
                $previous = $shipmentOperations->successfulResponse((int) $shipment->id, 'create');
                if ($previous !== null) {
                    $shipments->updateProviderData($shipment, $previous);
                    $outbox->markDispatched($event->deduplication_key);
                    return;
                }
                $shipmentOperations->start((int) $shipment->id, 'create', $key);
                $result = $providers->create($shipment);
                $shipmentOperations->complete((int) $shipment->id, 'create', 'provider_created', data_get($result, 'metadata.provider_reference'), $result);
                $shipments->updateProviderData($shipment, $result);
            }
            $outbox->markDispatched($event->deduplication_key);
        } catch (\Throwable $exception) {
            $outbox->markFailed($event->deduplication_key, $exception->getMessage());
            throw $exception;
        }
    }
}
