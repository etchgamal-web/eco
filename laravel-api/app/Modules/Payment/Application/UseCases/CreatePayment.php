<?php

namespace App\Modules\Payment\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException;
use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Contracts\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Contracts\PaymentOperationRepositoryInterface;
use App\Modules\Payment\Domain\Exceptions\PaymentAmountMismatchException;
use App\Modules\Payment\Domain\Exceptions\PaymentException;
use App\Modules\Payment\Domain\Exceptions\PaymentFailedException;
use App\Modules\Payment\Domain\Exceptions\PaymentInProgressException;
use App\Modules\Payment\Domain\ValueObjects\PaymentData;
use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;
use Illuminate\Support\Str;

final class CreatePayment
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authentication,
        private readonly OrderRepositoryInterface $orders,
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentOperationRepositoryInterface $operations,
        private readonly OutboxEventRepositoryInterface $outbox,
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
            if (in_array($claim->payment->status, ['pending', 'provider_created', 'confirmed', 'paid', 'refunded', 'failed'], true)) {
                return $claim->payment;
            }
            if (! in_array($claim->payment->status, ['processing', 'initiating'], true)) {
                throw new PaymentInProgressException('Payment is already being initiated. Retry with the same idempotency key.');
            }
        }

        $previousResult = $this->operations->successfulResponse((int) $claim->payment->id, 'create');
        if ($previousResult !== null) {
            return $this->payments->updateStatus($claim->payment, $previousResult['_operation_status'] ?? 'provider_created', [
                'provider_reference' => $previousResult['provider_reference'] ?? null,
                'metadata' => $previousResult['metadata'] ?? $claim->payment->metadata,
            ]);
        }
        $this->operations->start((int) $claim->payment->id, 'create', $data->idempotencyKey);
        $leaseToken = (string) Str::uuid();
        if (! $this->operations->acquireLease((int) $claim->payment->id, 'create', $leaseToken)) {
            throw new PaymentInProgressException('Payment operation is currently owned by another worker.');
        }

        try {
            $result = $this->gateway->createPayment($order, $data->method, $data->idempotencyKey);
            if (($result['status'] ?? null) === 'failed') {
                throw new PaymentFailedException('Payment creation failed.');
            }
            $paymentStatus = ($result['status'] ?? null) === 'paid' ? 'confirmed' : (($result['status'] ?? null) === 'pending' ? 'pending' : (($result['provider_reference'] ?? null) !== null ? 'provider_created' : 'pending'));
            $this->operations->complete((int) $claim->payment->id, 'create', $paymentStatus, $result['provider_reference'] ?? null, $result);
            $this->outbox->markDispatched('payment:create:' . $data->idempotencyKey);
        } catch (\Throwable $exception) {
            $this->operations->fail((int) $claim->payment->id, 'create', $exception->getMessage(), ! ($exception instanceof PaymentFailedException));
            $this->outbox->markFailed('payment:create:' . $data->idempotencyKey, $exception->getMessage());
            $this->payments->updateStatus($claim->payment, $exception instanceof PaymentFailedException ? 'failed' : 'processing', [
                'metadata' => [
                    'failure' => $exception->getMessage(),
                    'reconciliation_required' => ! ($exception instanceof PaymentFailedException),
                ],
            ]);
            throw $exception;
        }

        return $this->payments->updateStatus($claim->payment, $paymentStatus, [
            'provider_reference' => $result['provider_reference'] ?? null,
            'metadata' => $result['metadata'] ?? null,
        ]);
    }
}
