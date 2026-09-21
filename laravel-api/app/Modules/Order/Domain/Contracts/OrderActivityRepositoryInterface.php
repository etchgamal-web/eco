<?php

namespace App\Modules\Order\Domain\Contracts;

interface OrderActivityRepositoryInterface
{
    public function record(int $orderId, string $event, ?object $actor = null, string $source = 'system', ?string $fromStatus = null, ?string $toStatus = null, array $metadata = []): object;

    public function listForOrder(int $orderId): iterable;
}
