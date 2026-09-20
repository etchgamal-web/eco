<?php

namespace App\Modules\Payment\Application\UseCases;

use App\Models\AuditLog;

use App\Modules\Order\Domain\Contracts\TransactionManagerInterface;
use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Exceptions\InvalidPaymentTransitionException;
use App\Modules\Payment\Domain\Exceptions\PaymentException;
use App\Modules\Payment\Domain\Exceptions\PaymentFailedException;

final class ConfirmPayment
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentGatewayInterface $gateway,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    public function execute(int $paymentId): object
    {
        $payment = $this->payments->find($paymentId);
        if (! in_array($payment->status, ['pending', 'processing'], true)) {
            throw InvalidPaymentTransitionException::from($payment->status, 'confirmed');
        }
        $result = $this->gateway->confirmPayment($payment);
        if (! in_array(($result['status'] ?? null), ['paid', 'confirmed'], true)) {
            throw new PaymentFailedException('Payment confirmation failed.');
        }

        return $this->transactions->run(function () use ($paymentId, $result): object {
            $locked = $this->payments->findForUpdate($paymentId);
            if (! in_array($locked->status, ['pending', 'processing'], true)) {
                throw InvalidPaymentTransitionException::from($locked->status, 'confirmed');
            }
            $confirmed = $this->payments->updateStatus($locked, 'paid', [
                'provider_reference' => $result['provider_reference'] ?? $locked->provider_reference,
                'metadata' => $result['metadata'] ?? $locked->metadata,
            ]);
            AuditLog::query()->create(['actor_id' => auth()->id(), 'action' => 'payment.confirmed', 'target_type' => get_class($confirmed), 'target_id' => $confirmed->id, 'metadata' => ['provider_reference' => $confirmed->provider_reference]]);

            return $confirmed;
        });
    }
}
