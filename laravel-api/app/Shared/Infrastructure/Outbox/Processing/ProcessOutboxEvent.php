<?php

namespace App\Shared\Infrastructure\Outbox\Processing;

use App\Shared\Infrastructure\Outbox\Processing\OutboxProcessor;
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

    public function __construct(public readonly int $eventId, public readonly string $claimToken)
    {
        $this->timeout = (int) config('outbox.job_timeout_seconds', 120);
    }

    public function handle(OutboxProcessor $dispatcher): void
    {
        $dispatcher->dispatch($this->eventId, $this->claimToken);
    }

    public function failed(Throwable $exception): void
    {
        app(OutboxProcessor::class)->failed($this->eventId, $this->claimToken, $exception);
    }
}
