<?php

namespace App\Modules\Payment\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Contracts\PaymentOperationRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Exceptions\InvalidPaymentTransitionException;
use App\Modules\Payment\Domain\Exceptions\PaymentFailedException;
use App\Shared\Domain\Contracts\OutboxRepositoryInterface;
use App\Shared\Domain\Data\OutboxMessage;
use App\Modules\Shared\Domain\Contracts\TransactionManagerInterface;
use App\Modules\Staff\Domain\Contracts\AuditLogRepositoryInterface;
use Illuminate\Support\Str;

final class RefundPayment
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentOperationRepositoryInterface $operations,
        private readonly PaymentGatewayInterface $gateway,
        private readonly OrderRepositoryInterface $orders,
        private readonly TransactionManagerInterface $transactions,
        private readonly OutboxRepositoryInterface $outbox,
        private readonly AuthenticationServiceInterface $authentication,
        private readonly AuditLogRepositoryInterface $audit,
    ) {}

    public function execute(int $paymentId, ?int $requestedAmount = null): object
    {
        $payment = $this->payments->find($paymentId);
        if (! in_array($payment->status, ['paid', 'confirmed', 'partially_refunded'], true)) {
            throw InvalidPaymentTransitionException::from($payment->status, 'refunded');
        }
        $refundAmount = $requestedAmount ?? (int) $payment->amount;
        $operationKey = 'refund:'.$payment->id.':'.$refundAmount.':'.($payment->provider_reference ?: $payment->idempotency_key);
        $previous = $this->operations->successfulResponse((int) $payment->id, 'refund', $operationKey);
        if ($previous !== null) {
            return $this->transactions->run(function () use ($paymentId, $payment, $previous, $refundAmount): object {
                $locked = $this->payments->findForUpdate($paymentId);
                if (in_array($locked->status, ['paid', 'confirmed', 'partially_refunded'], true)) {
                    $confirmedAmount = (int) ($previous['confirmed_amount'] ?? $previous['refunded_amount'] ?? $refundAmount);
                    $nextStatus = $this->operations->refundedAmount($paymentId) >= (int) $locked->amount ? 'refunded' : 'partially_refunded';
                    $refunded = $this->payments->updateStatus($locked, $nextStatus, ['metadata' => array_merge((array) $locked->metadata, (array) ($previous['metadata'] ?? []), ['refund_confirmed_amount' => $confirmedAmount])]);
                    $order = $this->orders->find($payment->order_id);
                    if ($nextStatus === 'refunded' && $order->status === 'delivered') {
                        $this->orders->markRefunded($payment->order_id);
                    }

                    return $refunded;
                }

                return $locked;
            });
        }
        $remainingAmount = (int) $payment->amount - $this->operations->refundedAmount((int) $payment->id);
        if ($refundAmount <= 0 || $refundAmount > $remainingAmount) {
            throw new PaymentFailedException('Refund amount exceeds the remaining refundable payment amount.');
        }
        $this->operations->start((int) $payment->id, 'refund', $operationKey, $refundAmount);
        $leaseToken = (string) Str::uuid();
        if (! $this->operations->acquireLease((int) $payment->id, 'refund', $leaseToken, 300, $operationKey)) {
            throw new PaymentFailedException('Refund operation is already in progress.');
        }
        try {
            $refundPayment = clone $payment;
            if ($requestedAmount !== null) $refundPayment->amount = $requestedAmount;
            $result = $this->gateway->refundPayment($refundPayment);
            if (($result['status'] ?? null) !== 'refunded') {
                throw new PaymentFailedException('Payment refund failed.');
            }
            $confirmedAmount = (int) ($result['refunded_amount'] ?? $result['amount'] ?? $refundAmount);
            if ($confirmedAmount <= 0 || $confirmedAmount > $remainingAmount) {
                throw new PaymentFailedException('Provider confirmed an invalid refund amount.');
            }
            $result['confirmed_amount'] = $confirmedAmount;
            $this->operations->complete((int) $payment->id, 'refund', 'confirmed', $payment->provider_reference, $result, $leaseToken, $confirmedAmount);
            $this->outbox->add(new OutboxMessage('payment.refund.completed', 'payment', (int) $payment->id, ['payment_id' => $payment->id, 'provider_reference' => $payment->provider_reference, 'confirmed_amount' => $confirmedAmount], deduplicationKey: 'payment:'.$operationKey));
        } catch (\Throwable $exception) {
            if ($exception instanceof PaymentFailedException) {
                $this->operations->fail((int) $payment->id, 'refund', $exception->getMessage(), false, $leaseToken);
            } else {
                $this->operations->failAmbiguous((int) $payment->id, 'refund', $exception->getMessage(), $leaseToken);
            }
            throw $exception;
        }

        return $this->transactions->run(function () use ($paymentId, $payment, $result, $refundAmount): object {
            $locked = $this->payments->findForUpdate($paymentId);
            if (! in_array($locked->status, ['paid', 'confirmed', 'partially_refunded'], true)) {
                throw InvalidPaymentTransitionException::from($locked->status, 'refunded');
            }
            $confirmedAmount = (int) ($result['confirmed_amount'] ?? $refundAmount);
            $refundedTotal = $this->operations->refundedAmount($paymentId);
            $nextStatus = $refundedTotal >= (int) $locked->amount ? 'refunded' : 'partially_refunded';
            $refunded = $this->payments->updateStatus($locked, $nextStatus, [
                'metadata' => array_merge((array) $locked->metadata, (array) ($result['metadata'] ?? []), ['refund_confirmed_amount' => $confirmedAmount]),
            ]);
            $order = $this->orders->find($payment->order_id);
            if ($nextStatus === 'refunded' && $order->status === 'delivered') {
                $this->orders->markRefunded($payment->order_id);
            }
            $this->audit->record($this->authentication->user(), 'payment.refunded', get_class($refunded), $refunded->id, ['provider_reference' => $refunded->provider_reference]);

            return $refunded;
        });
    }
}
