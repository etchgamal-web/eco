<?php

namespace App\Modules\Payment\Application\UseCases;

use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Domain\Contracts\TransactionManagerInterface;
use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Contracts\PaymentOperationRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Exceptions\PaymentException;
use Illuminate\Support\Str;

final class ReconcilePayment
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentGatewayInterface $gateway,
        private readonly PaymentOperationRepositoryInterface $operations,
        private readonly OrderRepositoryInterface $orders,
        private readonly TransactionManagerInterface $transactions,
    ) {
    }

    public function execute(int $paymentId): object
    {
        $payment = $this->payments->find($paymentId);
        if (! in_array($payment->status, ['processing', 'provider_created'], true)) {
            return $payment;
        }
        $leaseToken = (string) Str::uuid();
        if (! $this->operations->acquireLease((int) $payment->id, 'create', $leaseToken)) {
            throw new PaymentException('Payment reconciliation is already in progress.');
        }
        $result = $this->gateway->reconcilePayment($payment);
        $status = (string) ($result['status'] ?? 'processing');
        if ($status === 'pending' && $payment->status === 'provider_created') {
            $status = 'provider_created';
        }
        if (! in_array($status, ['pending', 'processing', 'provider_created', 'confirmed', 'failed'], true)) {
            throw new PaymentException('Invalid provider reconciliation status.');
        }
        return $this->transactions->run(function () use ($payment, $result, $status): object {
            $locked = $this->payments->findForUpdate((int) $payment->id);
            $updated = $this->payments->updateStatus($locked, $status, [
                'provider_reference' => $result['provider_reference'] ?? $locked->provider_reference,
                'metadata' => array_merge((array) $locked->metadata, (array) ($result['metadata'] ?? [])),
            ]);
            $this->operations->complete((int) $updated->id, 'create', $status === 'confirmed' ? 'confirmed' : $status, $result['provider_reference'] ?? null, $result);
            if ($status === 'confirmed' && $updated->order->status === 'pending') {
                $this->orders->updateStatus((int) $updated->order_id, 'confirmed');
            }
            return $updated;
        });
    }
}
