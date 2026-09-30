<?php

namespace App\Modules\Payment\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException;
use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Exceptions\PaymentAmountMismatchException;
use App\Modules\Payment\Domain\Exceptions\PaymentException;
use App\Modules\Payment\Domain\Exceptions\PaymentInProgressException;
use App\Modules\Payment\Domain\ValueObjects\PaymentData;

final class CreatePayment
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authentication,
        private readonly OrderRepositoryInterface $orders,
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentGatewayInterface $gateway,
    ) {}

    public function execute(int $orderId, PaymentData $data): object
    {
        $user = $this->authentication->user();
        if ($user === null) {
            throw new AuthenticationException('Unauthenticated.');
        }
        if (! $this->gateway->supports($data->method)) {
            throw new PaymentException('Unsupported payment method.');
        }

        $order = $this->orders->findForUser($user->id, $orderId);
        if ($data->currency !== $order->currency) {
            throw new PaymentAmountMismatchException('Payment currency does not match the order.');
        }
        if ($data->amount !== null && $data->amount !== $order->total_amount) {
            throw new PaymentAmountMismatchException('Payment amount does not match the order.');
        }

        $claim = $this->payments->claim($data->idempotencyKey, [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'method' => $data->method,
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'metadata' => ['idempotency_key' => $data->idempotencyKey],
        ]);
        if (! $claim->acquired) {
            if ($claim->payment->order_id !== $order->id || $claim->payment->user_id !== $user->id) {
                throw new PaymentException('Idempotency key belongs to another order.');
            }
            if (in_array($claim->payment->status, ['pending', 'provider_created', 'confirmed', 'paid', 'partially_refunded', 'refunded', 'failed'], true)) {
                return $claim->payment;
            }
            if (! in_array($claim->payment->status, ['processing', 'initiating'], true)) {
                throw new PaymentInProgressException('Payment is already being initiated. Retry with the same idempotency key.');
            }
        }

        // The outbox row is written by the payment repository transaction.
        // Provider I/O is intentionally performed only by PaymentOutboxHandler.
        return $this->payments->updateStatus($claim->payment, 'pending');
    }
}
