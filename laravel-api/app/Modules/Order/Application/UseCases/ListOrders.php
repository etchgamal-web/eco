<?php

namespace App\Modules\Order\Application\UseCases;

use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;

final class ListOrders
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function execute(array $filters = []): iterable
    {
        return $this->orders->listAll($filters);
    }
}
