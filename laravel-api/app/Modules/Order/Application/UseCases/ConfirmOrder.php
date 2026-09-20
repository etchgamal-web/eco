<?php

namespace App\Modules\Order\Application\UseCases;

use App\Modules\Order\Domain\Contracts\OrderReviewRepositoryInterface;

final class ConfirmOrder
{
    public function __construct(private readonly OrderReviewRepositoryInterface $reviews) {}

    public function execute(int $orderId, int $confirmedBy): object
    {
        return $this->reviews->confirm($orderId, $confirmedBy);
    }
}

