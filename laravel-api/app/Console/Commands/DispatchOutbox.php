<?php

namespace App\Console\Commands;

use App\Models\OutboxEvent;
use App\Modules\Shared\Application\Jobs\ProcessOutboxEvent;
use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;
use Illuminate\Console\Command;

if (! class_exists(__NAMESPACE__ . '\\DispatchOutbox', false)) {
final class DispatchOutbox extends Command
{
    protected $signature = 'outbox:dispatch {--limit=100 : Maximum events to enqueue in one pass}';
    protected $description = 'Dispatch pending payment, shipment, and social outbox events to the queue';

    public function handle(OutboxEventRepositoryInterface $outbox): int
    {
        $count = 0;
        $leaseMinutes = (int) config('outbox.lease_minutes', 5);
        OutboxEvent::query()->where(function ($query) use ($leaseMinutes): void {
                $query->where('status', 'pending')->where(function ($pending): void {
                    $pending->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
                })->orWhere(function ($processing) use ($leaseMinutes): void {
                    $processing->where('status', 'processing')->where('updated_at', '<=', now()->subMinutes($leaseMinutes));
                });
            })
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get()
            ->each(function (OutboxEvent $event) use (&$count, $outbox): void {
                if ($outbox->claim((int) $event->id, $leaseMinutes)) {
                    ProcessOutboxEvent::dispatch($event->id);
                    $count++;
                }
            });

        $this->info("Dispatched {$count} outbox event(s).");
        return self::SUCCESS;
    }
}
}
