<?php

namespace App\Modules\Shipping\Domain\Contracts;

use App\Modules\Shipping\Domain\ValueObjects\ShippingPricingBreakdown;

interface ShippingPricingCalculatorInterface
{
    public function calculateBreakdown(object $order, object $method, string $providerCode): ShippingPricingBreakdown;
}
