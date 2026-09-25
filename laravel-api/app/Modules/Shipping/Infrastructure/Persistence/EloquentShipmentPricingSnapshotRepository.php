<?php

namespace App\Modules\Shipping\Infrastructure\Persistence;

use App\Modules\Shipping\Infrastructure\Models\ShipmentPricingSnapshot;
use App\Modules\Shipping\Domain\Contracts\ShipmentPricingSnapshotRepositoryInterface;

final class EloquentShipmentPricingSnapshotRepository implements ShipmentPricingSnapshotRepositoryInterface
{
    public function create(array $attributes): object
    {
        return ShipmentPricingSnapshot::query()->create($attributes);
    }
}
