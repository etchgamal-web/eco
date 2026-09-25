<?php

namespace App\Modules\Shared\Application\Jobs;

use App\Modules\Shared\Application\Outbox\OutboxEventDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class ProcessOutboxEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public readonly int $eventId)
    {
        $this->timeout = (int) config('outbox.job_timeout_seconds', 120);
    }

    public function handle(OutboxEventDispatcher $dispatcher): void
    {
        $dispatcher->dispatch($this->eventId);
    }

    public function failed(Throwable $exception): void
    {
        app(OutboxEventDispatcher::class)->failed($this->eventId, $exception);
    }
}
