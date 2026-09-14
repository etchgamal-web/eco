<?php

namespace App\Modules\Payment\Application\UseCases;

use App\Models\AuditLog;
use App\Models\OutboxEvent;

use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Domain\Contracts\TransactionManagerInterface;
use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentOperationRepositoryInterface;
use App\Modules\Payment\Domain\Exceptions\InvalidPaymentTransitionException;
use App\Modules\Payment\Domain\Exceptions\PaymentFailedException;
use Illuminate\Support\Str;

final class RefundPayment
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentOperationRepositoryInterface $operations,
        private readonly PaymentGatewayInterface $gateway,
        private readonly OrderRepositoryInterface $orders,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    public function execute(int $paymentId): object
    {
        $payment = $this->payments->find($paymentId);
        if (! in_array($payment->status, ['paid', 'confirmed'], true)) {
            throw InvalidPaymentTransitionException::from($payment->status, 'refunded');
        }
        $operationKey = 'refund:' . $payment->id . ':' . ($payment->provider_reference ?: $payment->idempotency_key);
        $previous = $this->operations->successfulResponse((int) $payment->id, 'refund');
        if ($previous !== null) {
            return $this->transactions->run(function () use ($paymentId, $payment, $previous): object {
                $locked = $this->payments->findForUpdate($paymentId);
                if (in_array($locked->status, ['paid', 'confirmed'], true)) {
                    $refunded = $this->payments->updateStatus($locked, 'refunded', ['metadata' => $previous['metadata'] ?? $locked->metadata]);
                    $this->orders->markRefunded($payment->order_id);
                    return $refunded;
                }
                return $locked;
            });
        }
        $this->operations->start((int) $payment->id, 'refund', $operationKey);
        $leaseToken = (string) Str::uuid();
        if (! $this->operations->acquireLease((int) $payment->id, 'refund', $leaseToken)) {
            throw new PaymentFailedException('Refund operation is already in progress.');
        }
        try {
            $result = $this->gateway->refundPayment($payment);
            if (($result['status'] ?? null) !== 'refunded') {
                throw new PaymentFailedException('Payment refund failed.');
            }
            $this->operations->complete((int) $payment->id, 'refund', 'confirmed', $payment->provider_reference, $result);
            OutboxEvent::query()->firstOrCreate(['deduplication_key' => 'payment:refund:' . $payment->id], ['aggregate_type' => 'payment', 'aggregate_id' => $payment->id, 'event_type' => 'payment.refund.completed', 'status' => 'pending', 'payload' => ['payment_id' => $payment->id, 'provider_reference' => $payment->provider_reference]]);
        } catch (\Throwable $exception) {
            $this->operations->fail((int) $payment->id, 'refund', $exception->getMessage(), ! ($exception instanceof PaymentFailedException));
            throw $exception;
        }
        return $this->transactions->run(function () use ($paymentId, $payment, $result): object {
            $locked = $this->payments->findForUpdate($paymentId);
            if (! in_array($locked->status, ['paid', 'confirmed'], true)) {
                throw InvalidPaymentTransitionException::from($locked->status, 'refunded');
            }
            $refunded = $this->payments->updateStatus($locked, 'refunded', [
                'metadata' => $result['metadata'] ?? $locked->metadata,
            ]);
            $this->orders->markRefunded($payment->order_id);
            AuditLog::query()->create(['actor_id' => auth()->id(), 'action' => 'payment.refunded', 'target_type' => get_class($refunded), 'target_id' => $refunded->id, 'metadata' => ['provider_reference' => $refunded->provider_reference]]);

            return $refunded;
        });
    }
}
