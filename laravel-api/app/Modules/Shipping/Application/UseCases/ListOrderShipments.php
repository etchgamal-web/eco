<?php

namespace App\Modules\Shipping\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException;
use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Shipping\Domain\Contracts\ShipmentRepositoryInterface;

final class ListOrderShipments
{
    public function __construct(private readonly AuthenticationServiceInterface $authentication, private readonly OrderRepositoryInterface $orders, private readonly ShipmentRepositoryInterface $shipments) {}
    public function execute(int $orderId): iterable
    {
        $user = $this->authentication->user();
        if ($user === null) throw new AuthenticationException('Unauthenticated.');
        $this->orders->findForUser($user->id, $orderId);
        return $this->shipments->listForUserOrder($user->id, $orderId);
    }
}
