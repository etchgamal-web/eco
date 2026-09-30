<?php

namespace App\Modules\Payment\Application\UseCases;

use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Domain\Contracts\ReturnRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Contracts\PaymentOperationRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Exceptions\PaymentException;
use App\Modules\Shared\Domain\Contracts\TransactionManagerInterface;
use Illuminate\Support\Str;

final class ReconcilePayment
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentGatewayInterface $gateway,
        private readonly PaymentOperationRepositoryInterface $operations,
        private readonly OrderRepositoryInterface $orders,
        private readonly ReturnRepositoryInterface $returns,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    public function execute(int $paymentId, ?int $operationId = null): object
    {
        $payment = $this->payments->find($paymentId);
        $operation = $operationId === null ? null : $this->operations->ambiguousOperation((int) $payment->id, $operationId);
        if ($operationId !== null && $operation === null) {
            throw new PaymentException('The requested reconciliation operation is not an unresolved operation for this payment.');
        }
        if ($operation === null) {
            $operation = 'create';
            if (! in_array($payment->status, ['processing', 'provider_created'], true)) return $payment;
        }

        $returnId = $operationId === null ? null : $this->operations->returnId((int) $payment->id, $operationId);

        $leaseToken = (string) Str::uuid();
        if (! $this->operations->acquireLease((int) $payment->id, $operation, $leaseToken, 300, null, $operationId !== null, $operationId)) {
            throw new PaymentException('Payment reconciliation is already in progress or blocked by an unresolved ambiguous operation.');
        }

        try {
            if ($operation === 'refund') return $this->reconcileRefund($payment, $leaseToken, $operationId, $returnId);

            $result = $this->gateway->reconcilePayment($payment);
            $status = (string) ($result['status'] ?? 'processing');
            if ($status === 'pending' && $payment->status === 'provider_created') $status = 'provider_created';
            if (! in_array($status, ['pending', 'processing', 'provider_created', 'confirmed', 'failed'], true)) {
                throw new PaymentException('Invalid provider reconciliation status.');
            }
            if ($operation === 'confirm') {
                if ($status === 'pending' || $status === 'processing' || $status === 'provider_created') {
                    $this->operations->failAmbiguous((int) $payment->id, 'confirm', 'Provider confirmation remains unresolved.', $leaseToken);
                    return $payment;
                }
                return $this->transactions->run(function () use ($payment, $result, $status, $leaseToken): object {
                    $locked = $this->payments->findForUpdate((int) $payment->id);
                    $updated = $this->payments->updateStatus($locked, $status === 'confirmed' ? 'paid' : 'failed', [
                        'provider_reference' => $result['provider_reference'] ?? $locked->provider_reference,
                        'metadata' => array_merge((array) $locked->metadata, (array) ($result['metadata'] ?? [])),
                    ]);
                    $this->operations->complete((int) $updated->id, 'confirm', $status === 'confirmed' ? 'confirmed' : 'failed', $result['provider_reference'] ?? null, $result, $leaseToken);
                    return $updated;
                });
            }

            return $this->transactions->run(function () use ($payment, $result, $status, $leaseToken): object {
                $locked = $this->payments->findForUpdate((int) $payment->id);
                $updated = $this->payments->updateStatus($locked, $status, [
                    'provider_reference' => $result['provider_reference'] ?? $locked->provider_reference,
                    'metadata' => array_merge((array) $locked->metadata, (array) ($result['metadata'] ?? [])),
                ]);
                $this->operations->complete((int) $updated->id, 'create', $status === 'confirmed' ? 'confirmed' : $status, $result['provider_reference'] ?? null, $result, $leaseToken);
                if ($status === 'confirmed' && $updated->order->status === 'pending') $this->orders->updateStatus((int) $updated->order_id, 'confirmed');
                return $updated;
            });
        } catch (\Throwable $exception) {
            $this->operations->failAmbiguous((int) $payment->id, $operation, $exception->getMessage(), $leaseToken);
            throw $exception;
        }
    }

    private function reconcileRefund(object $payment, string $leaseToken, ?int $operationId, ?int $returnId): object
    {
        $result = $this->gateway->reconcileRefund($payment);
        $status = (string) ($result['status'] ?? 'ambiguous');
        if ($status === 'ambiguous') {
            $this->operations->failAmbiguous((int) $payment->id, 'refund', 'Provider refund result remains unresolved.', $leaseToken);
            return $payment;
        }
        if (! in_array($status, ['refunded', 'failed'], true)) throw new PaymentException('Invalid provider refund reconciliation status.');
        if ($status === 'failed') {
            $this->operations->complete((int) $payment->id, 'refund', 'failed', $result['provider_reference'] ?? null, $result, $leaseToken);
            return $payment;
        }

        $confirmedAmount = (int) ($result['confirmed_amount'] ?? $result['refunded_amount'] ?? $this->operations->requestedAmount((int) $payment->id, 'refund', $operationId) ?? 0);
        if ($confirmedAmount <= 0) throw new PaymentException('Refund reconciliation did not confirm an amount.');

        return $this->transactions->run(function () use ($payment, $result, $leaseToken, $confirmedAmount, $returnId): object {
            $locked = $this->payments->findForUpdate((int) $payment->id);
            $refundedTotal = $this->operations->refundedAmount((int) $payment->id) + $confirmedAmount;
            $nextStatus = $refundedTotal >= (int) $locked->amount ? 'refunded' : 'partially_refunded';
            $refunded = $this->payments->updateStatus($locked, $nextStatus, ['metadata' => array_merge((array) $locked->metadata, (array) ($result['metadata'] ?? []))]);
            $this->operations->complete((int) $refunded->id, 'refund', 'confirmed', $result['provider_reference'] ?? $refunded->provider_reference, $result, $leaseToken, $confirmedAmount);
            if ($nextStatus === 'refunded' && $refunded->order->status === 'delivered') $this->orders->markRefunded((int) $refunded->order_id);
            $this->returns->completeRefundForPayment((int) $refunded->id, $confirmedAmount, $returnId);
            return $refunded;
        });
    }
}
