<?php

namespace App\Modules\Shipping\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException;
use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShipmentRepositoryInterface;

final class UpdateShipmentStatus
{
    public function __construct(private readonly AuthenticationServiceInterface $authentication, private readonly ShipmentRepositoryInterface $shipments, private readonly OrderRepositoryInterface $orders) {}
    public function execute(int $shipmentId, string $status, ?string $note = null): object
    {
        $user = $this->authentication->user();
        if ($user === null) throw new AuthenticationException('Unauthenticated.');
        $shipment = $this->shipments->updateStatus($this->shipments->find($shipmentId), $status, $user->id, $note);
        if ($status === 'delivered') {
            $order = $this->orders->find($shipment->order_id);
            if ($order->status === 'shipped') {
                $this->orders->updateStatus($order->id, 'delivered');
            }
        }
        return $shipment;
    }
}
