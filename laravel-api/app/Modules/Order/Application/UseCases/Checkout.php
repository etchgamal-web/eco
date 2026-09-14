<?php

namespace App\Modules\Order\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException;
use App\Modules\Order\Domain\ValueObjects\CheckoutData;
use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Domain\Contracts\TransactionManagerInterface;
use App\Modules\Payment\Application\UseCases\CreatePayment;
use App\Modules\Payment\Domain\ValueObjects\PaymentData;
use App\Modules\Shipping\Application\UseCases\CreateShipment;
use App\Modules\Shipping\Domain\ValueObjects\CreateShipmentData;
final class Checkout
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authentication,
        private readonly OrderRepositoryInterface $orders,
        private readonly TransactionManagerInterface $transactions,
        private readonly CreateShipment $createShipment,
        private readonly CreatePayment $createPayment,
    ) {}

    public function execute(CheckoutData $data): object
    {
        $user = $this->authentication->user();
        if ($user === null && $data->guestItems === []) {
            throw new AuthenticationException('Unauthenticated.');
        }

        // Keep only local order, inventory, and shipment state in this
        // transaction. A remote gateway call must never run under it: a
        // database rollback cannot undo a successful external charge.
        $order = $this->transactions->run(function () use ($data, $user): object {
            $order = $user === null
                ? $this->orders->checkoutGuest($data->guestItems, $data->guestDetails, $data->currency, $data->idempotencyKey, $data->couponCode)
                : $this->orders->checkout($user->id, $data->addressId, $data->currency, $data->idempotencyKey, $data->couponCode);

            if ($data->shippingMethodId !== null) {
                $shipment = $this->createShipment->execute($order->id, new CreateShipmentData(
                    $data->shippingMethodId,
                    $data->shippingIdempotencyKey ?? $data->idempotencyKey ?? ('shipment-' . $order->id),
                ));
                if ((int) $order->shipping_amount === 0) {
                    $order = $this->orders->addShippingFee($order->id, (int) $shipment->fee);
                }
            }

            return $order;
        });

        if ($data->paymentMethod !== null) {
            $this->createPayment->execute($order->id, new PaymentData(
                method: $data->paymentMethod,
                currency: $order->currency,
                idempotencyKey: $data->paymentIdempotencyKey ?? $data->idempotencyKey ?? ('payment-' . $order->id),
                amount: $order->total_amount,
            ));
        }

        return $user === null ? $this->orders->find($order->id) : $this->orders->findForUser($user->id, $order->id);
    }
}
