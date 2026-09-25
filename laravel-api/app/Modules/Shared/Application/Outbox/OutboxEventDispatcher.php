<?php

namespace App\Modules\Shared\Application\Outbox;

use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;

final class OutboxEventDispatcher
{
    /**
     * @param  iterable<OutboxEventHandlerInterface>  $handlers
     */
    public function __construct(
        private readonly OutboxEventRepositoryInterface $outbox,
        private readonly iterable $handlers,
    ) {}

    public function dispatch(int $eventId): void
    {
        $event = $this->outbox->find($eventId);
        if ($event === null || $event->status === 'dispatched') {
            return;
        }

        foreach ($this->handlers as $handler) {
            if ($handler->supports((string) $event->event_type)) {
                $handler->handle($event);

                return;
            }
        }

        // Some outbox rows are durable audit/integration records and do not
        // represent a queued side effect. Preserve them without retrying.
        $this->outbox->markDispatched((string) $event->deduplication_key);
    }

    public function failed(int $eventId, \Throwable $exception): void
    {
        $event = $this->outbox->find($eventId);
        if ($event === null) {
            return;
        }

        foreach ($this->handlers as $handler) {
            if ($handler->supports((string) $event->event_type)) {
                $handler->failed($event, $exception);

                return;
            }
        }

        $this->outbox->markFailed((string) $event->deduplication_key, $exception->getMessage());
    }
}
