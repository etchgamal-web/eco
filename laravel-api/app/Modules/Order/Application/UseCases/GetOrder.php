<?php

namespace App\Modules\Order\Application\UseCases;

use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;

final class GetOrder
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function execute(int $orderId): object
    {
        return $this->orders->find($orderId);
    }
}
