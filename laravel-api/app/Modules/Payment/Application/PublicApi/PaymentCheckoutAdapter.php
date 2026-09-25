<?php

namespace App\Modules\Payment\Application\PublicApi;

use App\Modules\Order\Domain\Contracts\PaymentInitiatorInterface;
use App\Modules\Payment\Application\UseCases\CreatePayment;
use App\Modules\Payment\Domain\ValueObjects\PaymentData;

final class PaymentCheckoutAdapter implements PaymentInitiatorInterface
{
    public function __construct(private readonly CreatePayment $createPayment) {}

    public function initiate(
        int $orderId,
        string $method,
        string $currency,
        string $idempotencyKey,
        ?int $amount = null,
    ): object {
        return $this->createPayment->execute($orderId, new PaymentData(
            method: $method,
            currency: $currency,
            idempotencyKey: $idempotencyKey,
            amount: $amount,
        ));
    }
}
