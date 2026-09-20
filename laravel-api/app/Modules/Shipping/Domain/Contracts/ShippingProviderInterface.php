<?php

namespace App\Modules\Shipping\Domain\Contracts;

interface ShippingProviderInterface
{
    public function supports(object $shipment): bool;

    public function create(object $shipment): array;

    /** Recover an already-created shipment without creating a new one. */
    public function recover(object $shipment): ?array;

    public function track(object $shipment): array;

    public function cancel(object $shipment): array;
}
