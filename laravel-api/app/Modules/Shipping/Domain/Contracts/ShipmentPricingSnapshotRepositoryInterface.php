<?php

namespace App\Modules\Shipping\Domain\Contracts;

interface ShipmentPricingSnapshotRepositoryInterface
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): object;
}
