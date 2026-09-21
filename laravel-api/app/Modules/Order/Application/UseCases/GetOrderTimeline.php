<?php

namespace App\Modules\Order\Application\UseCases;

use App\Modules\Order\Domain\Contracts\OrderActivityRepositoryInterface;

final class GetOrderTimeline
{
    public function __construct(private readonly OrderActivityRepositoryInterface $activities) {}

    public function execute(int $orderId): iterable
    {
        return $this->activities->listForOrder($orderId);
    }
}
