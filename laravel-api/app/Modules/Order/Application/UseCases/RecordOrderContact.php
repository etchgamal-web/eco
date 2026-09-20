<?php

namespace App\Modules\Order\Application\UseCases;

use App\Modules\Order\Domain\Contracts\OrderReviewRepositoryInterface;

final class RecordOrderContact
{
    public function __construct(private readonly OrderReviewRepositoryInterface $reviews) {}

    public function execute(int $orderId, string $contactResult, ?string $notes): object
    {
        return $this->reviews->recordContact($orderId, $contactResult, $notes);
    }
}

