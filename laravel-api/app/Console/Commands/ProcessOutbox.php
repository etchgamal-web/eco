<?php

namespace App\Console\Commands;

use App\Shared\Infrastructure\Outbox\Processing\ProcessOutboxEvent;
use App\Shared\Infrastructure\Outbox\Contracts\OutboxRepositoryInterface;
use Illuminate\Console\Command;

final class ProcessOutbox extends Command
{
    protected $signature = 'outbox:dispatch {--limit=100 : Maximum events to enqueue in one pass}';

    protected $description = 'Claim pending outbox events and dispatch them to the queue';

    public function handle(OutboxRepositoryInterface $outbox): int
    {
        $events = $outbox->claim((int) $this->option('limit'));
        foreach ($events as $event) {
            ProcessOutboxEvent::dispatch((int) $event->id, (string) $event->claim_token);
        }

        $this->info('Dispatched '.count($events).' outbox event(s).');

        return self::SUCCESS;
    }
}
