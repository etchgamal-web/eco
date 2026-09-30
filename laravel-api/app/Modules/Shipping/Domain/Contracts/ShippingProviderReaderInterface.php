<?php

namespace App\Modules\Shipping\Domain\Contracts;

interface ShippingProviderReaderInterface
{
    public function active(): iterable;
}
