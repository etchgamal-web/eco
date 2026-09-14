<?php

namespace App\Modules\Shipping\Domain\ValueObjects;

final readonly class CreateShipmentData
{
    public function __construct(
        public int $shippingMethodId,
        public string $idempotencyKey,
    ) {}
}
