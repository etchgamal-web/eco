<?php

namespace App\Modules\Shipping\Domain\ValueObjects;

final readonly class ShippingPricingBreakdown
{
    public function __construct(
        public int $total,
        public array $fees,
        public float $weight,
        public int $quantity,
        public ?string $zoneCode,
        public ?object $provider,
        public ?object $plan,
    ) {}
}
