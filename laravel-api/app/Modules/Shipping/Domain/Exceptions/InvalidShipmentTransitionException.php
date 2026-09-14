<?php

namespace App\Modules\Shipping\Domain\Exceptions;

final class InvalidShipmentTransitionException extends ShippingException
{
    public static function from(string $from, string $to): self
    {
        return new self("Shipment cannot transition from [{$from}] to [{$to}].");
    }
}
