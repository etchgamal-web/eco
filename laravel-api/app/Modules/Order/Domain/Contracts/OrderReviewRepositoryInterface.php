<?php

namespace App\Modules\Order\Domain\Contracts;

interface OrderReviewRepositoryInterface
{
    public function findForOrder(int $orderId): ?object;

    public function start(int $orderId, int $reviewerId): object;

    public function recordContact(int $orderId, string $contactResult, ?string $notes): object;

    public function confirm(int $orderId, int $confirmedBy): object;
}

