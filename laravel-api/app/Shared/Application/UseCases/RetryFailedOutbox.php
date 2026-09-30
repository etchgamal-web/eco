<?php

declare(strict_types=1);

namespace App\Shared\Application\UseCases;

use App\Shared\Domain\Contracts\OutboxRepositoryInterface;
use RuntimeException;

final class RetryFailedOutbox
{
    public function __construct(private readonly OutboxRepositoryInterface $outbox) {}

    public function execute(int $eventId): object
    {
        if (! $this->outbox->retryFailed($eventId)) {
            throw new RuntimeException('Only failed outbox events can be retried manually.');
        }

        return $this->outbox->find($eventId);
    }
}
