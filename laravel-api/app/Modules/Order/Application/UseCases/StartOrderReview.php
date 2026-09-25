<?php

namespace App\Modules\Order\Application\UseCases;

use App\Modules\Order\Domain\Contracts\OrderReviewRepositoryInterface;

final class StartOrderReview
{
    public function __construct(private readonly OrderReviewRepositoryInterface $reviews) {}

    public function execute(int $orderId, int $reviewerId): object
    {
        return $this->reviews->start($orderId, $reviewerId);
    }
}
