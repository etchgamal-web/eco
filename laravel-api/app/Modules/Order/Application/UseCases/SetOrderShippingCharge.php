<?php

namespace App\Modules\Order\Application\UseCases;

use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Domain\Exceptions\OrderActionNotAllowedException;

final class SetOrderShippingCharge
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function execute(int $orderId, int $customerShippingAmount): object
    {
        if ($customerShippingAmount < 0) {
            throw new OrderActionNotAllowedException('Customer shipping amount cannot be negative.');
        }

        return $this->orders->setShippingCharge($orderId, $customerShippingAmount);
    }
}
