<?php

namespace App\Modules\Order\Application\UseCases;

use App\Modules\Order\Domain\Contracts\OrderActivityRepositoryInterface;

final class RecordOrderActivity
{
    public function __construct(private readonly OrderActivityRepositoryInterface $activities) {}

    public function execute(int $orderId, string $event, ?object $actor = null, string $source = 'system', ?string $fromStatus = null, ?string $toStatus = null, array $metadata = []): object
    {
        return $this->activities->record($orderId, $event, $actor, $source, $fromStatus, $toStatus, $metadata);
    }
}
