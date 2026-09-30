<?php

namespace App\Modules\Shipping\Application\UseCases;

use App\Modules\Shipping\Domain\Contracts\ShipmentRepositoryInterface;

final class ListAdminShipments
{
    public function __construct(private readonly ShipmentRepositoryInterface $shipments) {}

    public function execute(array $filters = []): object
    {
        return $this->shipments->listForAdmin($filters);
    }
}
