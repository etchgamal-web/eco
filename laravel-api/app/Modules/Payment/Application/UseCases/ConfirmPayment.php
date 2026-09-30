<?php

namespace App\Modules\Payment\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Contracts\PaymentOperationRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Exceptions\InvalidPaymentTransitionException;
use App\Modules\Payment\Domain\Exceptions\PaymentFailedException;
use App\Modules\Shared\Domain\Contracts\TransactionManagerInterface;
use App\Modules\Staff\Domain\Contracts\AuditLogRepositoryInterface;
use Illuminate\Support\Str;

final class ConfirmPayment
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentGatewayInterface $gateway,
        private readonly PaymentOperationRepositoryInterface $operations,
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
        $operationKey = 'confirm:'.$payment->id.':'.($payment->provider_reference ?: $payment->idempotency_key);
        $previous = $this->operations->successfulResponse((int) $payment->id, 'confirm');
        $result = $previous ?? null;
        if ($result === null) {
            $this->operations->start((int) $payment->id, 'confirm', $operationKey);
            $leaseToken = (string) Str::uuid();
            if (! $this->operations->acquireLease((int) $payment->id, 'confirm', $leaseToken)) {
                throw new PaymentFailedException('Payment confirmation is already in progress or requires reconciliation.');
            }
            try {
                $result = $this->gateway->confirmPayment($payment);
                if (! in_array(($result['status'] ?? null), ['paid', 'confirmed'], true)) {
                    throw new PaymentFailedException('Payment confirmation failed.');
                }
                $this->operations->complete((int) $payment->id, 'confirm', 'confirmed', $result['provider_reference'] ?? $payment->provider_reference, $result, $leaseToken);
            } catch (\Throwable $exception) {
                if ($exception instanceof PaymentFailedException) {
                    $this->operations->fail((int) $payment->id, 'confirm', $exception->getMessage(), false, $leaseToken);
                } else {
                    $this->operations->failAmbiguous((int) $payment->id, 'confirm', $exception->getMessage(), $leaseToken);
                }
                throw $exception;
            }
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
