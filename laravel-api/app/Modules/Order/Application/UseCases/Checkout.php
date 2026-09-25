<?php

namespace App\Modules\Order\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException;
use App\Modules\Order\Application\Services\CheckoutOrderService;
use App\Modules\Order\Domain\Contracts\CheckoutPolicyInterface;
use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Domain\Contracts\PaymentInitiatorInterface;
use App\Modules\Order\Domain\ValueObjects\CheckoutData;

final class Checkout
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authentication,
        private readonly OrderRepositoryInterface $orders,
        private readonly CheckoutOrderService $checkoutOrders,
        private readonly PaymentInitiatorInterface $payments,
        private readonly CheckoutPolicyInterface $policy,
    ) {}

    public function execute(CheckoutData $data): object
    {
        $user = $this->authentication->user();
        if ($user === null && (! $this->policy->allowsGuestCheckout() || $data->guestItems === [])) {
            throw new AuthenticationException('Unauthenticated.');
        }

        // Checkout creates the order only. Shipment creation is an authorized
        // staff decision after order confirmation.
        $order = $this->checkoutOrders->execute($data, $user?->id);

        if ($data->paymentMethod !== null) {
            $this->payments->initiate(
                orderId: (int) $order->id,
                method: $data->paymentMethod,
                currency: (string) $order->currency,
                idempotencyKey: $data->paymentIdempotencyKey ?? $data->idempotencyKey ?? ('payment-'.$order->id),
                amount: (int) $order->total_amount,
            );
        }

        return $user === null ? $this->orders->find($order->id) : $this->orders->findForUser($user->id, $order->id);
    }
}
