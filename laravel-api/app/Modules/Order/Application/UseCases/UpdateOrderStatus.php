<?php

namespace App\Modules\Order\Application\UseCases;

use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;

final class UpdateOrderStatus
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function execute(int $orderId, string $status): object
    {
        if ($status === 'cancelled') {
            return $this->orders->cancel($orderId);
        }

        return $this->orders->updateStatus($orderId, $status);
    }
}
