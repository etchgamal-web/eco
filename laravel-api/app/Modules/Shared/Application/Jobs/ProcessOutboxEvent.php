<?php

namespace App\Modules\Shared\Application\Jobs;

use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentOperationRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShipmentOperationRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShipmentRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShippingProviderInterface;
use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialConnectionRepositoryInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialMessagingProviderInterface;
use App\Modules\SocialCommerce\Domain\Contracts\SocialInteractionRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class ProcessOutboxEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout;

    public function __construct(public readonly int $eventId)
    {
        $this->timeout = (int) config('outbox.job_timeout_seconds', 120);
    }

    public function failed(Throwable $exception): void
    {
        $outbox = app(OutboxEventRepositoryInterface::class);
        $event = $outbox->find($this->eventId);
        if (! $event) return;

        $exhausted = $outbox->markFailed(
            (string) $event->deduplication_key,
            $exception->getMessage(),
        );
        if ($exhausted && $event->aggregate_type === 'social_message') {
            app(SocialInteractionRepositoryInterface::class)->updateMessageStatus((int) $event->aggregate_id, 'failed');
        } elseif ($exhausted && $event->aggregate_type === 'social_comment') {
            app(SocialInteractionRepositoryInterface::class)->updateInteractionStatus((int) $event->aggregate_id, 'failed');
        }
    }

    public function handle(
        PaymentRepositoryInterface $payments,
        PaymentGatewayInterface $gateway,
        PaymentOperationRepositoryInterface $paymentOperations,
        ShipmentRepositoryInterface $shipments,
        ShippingProviderInterface $providers,
        ShipmentOperationRepositoryInterface $shipmentOperations,
        OrderRepositoryInterface $orders,
        OutboxEventRepositoryInterface $outbox,
        SocialConnectionRepositoryInterface $connections,
        SocialMessagingProviderInterface $socialProvider,
        SocialInteractionRepositoryInterface $socialInteractions,
    ): void {
        $event = $outbox->find($this->eventId);
        if ($event === null || $event->status === 'dispatched') {
            return;
        }

        try {
            if ($event->aggregate_type === 'social_message') {
                $message = $socialInteractions->findMessage((int) $event->aggregate_id);
                if (! $message || $message->status === 'sent') {
                    $outbox->markDispatched($event->deduplication_key);
                    return;
                }
                $payload = $event->payload;
                $connection = $connections->activeForChannel((string) ($payload['channel'] ?? ''));
                if (! $connection) throw new \RuntimeException('No active social connection for queued message.');
                $socialInteractions->updateMessageStatus((int) $event->aggregate_id, 'processing');
                $result = $socialProvider->sendMessage($connection, (string) $payload['recipient'], (string) $payload['body']);
                $socialInteractions->updateMessage((int) $event->aggregate_id, ['status' => 'sent', 'provider_message_id' => $result['provider_message_id'] ?? null, 'metadata' => $result]);
            } elseif ($event->aggregate_type === 'social_comment') {
                $interaction = $socialInteractions->find((int) $event->aggregate_id);
                if (! $interaction || $interaction->status === 'sent') {
                    $outbox->markDispatched($event->deduplication_key);
                    return;
                }
                $payload = $event->payload;
                $connection = $connections->activeForChannel((string) ($payload['channel'] ?? ''));
                if (! $connection) throw new \RuntimeException('No active social connection for queued comment.');
                $socialInteractions->updateInteractionStatus((int) $event->aggregate_id, 'processing');
                $result = $socialProvider->replyToComment($connection, (string) $payload['comment_id'], (string) $payload['body']);
                $socialInteractions->updateInteraction((int) $event->aggregate_id, ['status' => 'sent', 'provider_interaction_id' => $result['provider_message_id'] ?? null, 'metadata' => array_merge($interaction->metadata ?? [], $result)]);
            } elseif ($event->aggregate_type === 'payment') {
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
                if ($shipment->creation_status === 'created' || data_get($shipment->metadata, 'provider_reference')) {
                    $outbox->markDispatched($event->deduplication_key);
                    return;
                }
                if (! $providers->supports($shipment)) {
                    throw new \RuntimeException('The selected shipping provider is unavailable or not configured.');
                }
                $key = (string) $shipment->idempotency_key;
                $previous = $shipmentOperations->successfulResponse((int) $shipment->id, 'create');
                if ($previous !== null) {
                    $shipments->updateProviderData($shipment, $previous);
                    $outbox->markDispatched($event->deduplication_key);
                    return;
                }
                if ($shipmentOperations->hasAttempted((int) $shipment->id, 'create')) {
                    $recovered = $providers->recover($shipment);
                    if ($recovered === null) {
                        throw new \RuntimeException('Shipment creation was previously attempted, but the provider could not recover an existing shipment safely.');
                    }
                    $shipmentOperations->complete((int) $shipment->id, 'create', 'provider_created', data_get($recovered, 'metadata.provider_reference'), $recovered);
                    $createdShipment = $shipments->updateProviderData($shipment, $recovered);
                    $order = $orders->find((int) $createdShipment->order_id);
                    if ($order->status === 'processing') {
                        $orders->updateStatus((int) $order->id, 'shipped');
                    }
                    $outbox->markDispatched($event->deduplication_key);
                    return;
                }
                $shipments->markCreationPending($shipment);
                $shipmentOperations->start((int) $shipment->id, 'create', $key);
                $result = $providers->create($shipment);
                $shipmentOperations->complete((int) $shipment->id, 'create', 'provider_created', data_get($result, 'metadata.provider_reference'), $result);
                $createdShipment = $shipments->updateProviderData($shipment, $result);
                $order = $orders->find((int) $createdShipment->order_id);
                if ($order->status === 'processing') {
                    $orders->updateStatus((int) $order->id, 'shipped');
                }
            }
            $outbox->markDispatched($event->deduplication_key);
        } catch (\Throwable $exception) {
            if ($event->aggregate_type === 'shipment') {
                $shipment = $shipments->find((int) $event->aggregate_id);
                $shipments->markCreationFailed($shipment, $exception->getMessage());
            } elseif ($event->aggregate_type === 'social_message') {
                $socialInteractions->updateMessageStatus((int) $event->aggregate_id, 'retrying');
            } elseif ($event->aggregate_type === 'social_comment') {
                $socialInteractions->updateInteractionStatus((int) $event->aggregate_id, 'retrying');
            }
            $exhausted = $outbox->markFailed($event->deduplication_key, $exception->getMessage());
            if ($exhausted && $event->aggregate_type === 'social_message') {
                $socialInteractions->updateMessageStatus((int) $event->aggregate_id, 'failed');
            } elseif ($exhausted && $event->aggregate_type === 'social_comment') {
                $socialInteractions->updateInteractionStatus((int) $event->aggregate_id, 'failed');
            }
        }
    }
}
