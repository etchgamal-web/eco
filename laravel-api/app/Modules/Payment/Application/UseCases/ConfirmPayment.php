<?php

namespace App\Modules\Payment\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Exceptions\InvalidPaymentTransitionException;
use App\Modules\Payment\Domain\Exceptions\PaymentFailedException;
use App\Modules\Shared\Domain\Contracts\TransactionManagerInterface;
use App\Modules\Staff\Domain\Contracts\AuditLogRepositoryInterface;

final class ConfirmPayment
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentGatewayInterface $gateway,
        private readonly TransactionManagerInterface $transactions,
        private readonly AuthenticationServiceInterface $authentication,
        private readonly AuditLogRepositoryInterface $audit,
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
            $this->audit->record($this->authentication->user(), 'payment.confirmed', get_class($confirmed), $confirmed->id, ['provider_reference' => $confirmed->provider_reference]);

            return $confirmed;
        });
    }
}
