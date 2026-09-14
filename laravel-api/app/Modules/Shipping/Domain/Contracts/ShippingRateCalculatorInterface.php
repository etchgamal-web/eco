<?php

namespace App\Modules\Shipping\Domain\Contracts;

interface ShippingRateCalculatorInterface
{
    public function calculate(object $order, object $method): int;
}
