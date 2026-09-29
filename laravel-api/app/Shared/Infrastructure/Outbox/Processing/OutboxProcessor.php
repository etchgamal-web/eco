<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox\Processing;

use App\Shared\Infrastructure\Outbox\Contracts\OutboxRepositoryInterface;
use App\Shared\Domain\Contracts\OutboxEventHandlerInterface;

final class OutboxProcessor
{
    /** @param iterable<OutboxEventHandlerInterface> $handlers */
    public function __construct(
        private readonly OutboxRepositoryInterface $outbox,
        private readonly iterable $handlers,
    ) {}

    public function dispatch(int $eventId, string $claimToken): void
    {
        $event = $this->outbox->find($eventId);
        if ($event === null || $event->status !== 'processing' || $event->claim_token !== $claimToken) {
            return;
        }

        foreach ($this->handlers as $handler) {
            if ($handler->supports((string) $event->event_type)) {
                $handler->handle($event);
                return;
            }
        }

        $this->outbox->markFailed($eventId, $claimToken, 'Unsupported outbox event type: '.(string) $event->event_type);
    }

    public function failed(int $eventId, string $claimToken, \Throwable $exception): void
    {
        $event = $this->outbox->find($eventId);
        if ($event === null || $event->status !== 'processing' || $event->claim_token !== $claimToken) {
            return;
        }

        foreach ($this->handlers as $handler) {
            if ($handler->supports((string) $event->event_type)) {
                $handler->failed($event, $exception);
                return;
            }
        }

        $this->outbox->markFailed($eventId, $claimToken, $exception->getMessage());
    }
}
