<?php

namespace App\Console\Commands;

use App\Models\OutboxEvent;
use App\Modules\Shared\Application\Jobs\ProcessOutboxEvent;
use Illuminate\Console\Command;

if (! class_exists(__NAMESPACE__ . '\\DispatchOutbox', false)) {
final class DispatchOutbox extends Command
{
    protected $signature = 'outbox:dispatch {--limit=100 : Maximum events to enqueue in one pass}';
    protected $description = 'Dispatch pending payment and shipment outbox events to the queue';

    public function handle(): int
    {
        $count = 0;
        OutboxEvent::query()->where(function ($query): void {
                $query->where('status', 'pending')->where(function ($pending): void {
                    $pending->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
                })->orWhere(function ($processing): void {
                    $processing->where('status', 'processing')->where('updated_at', '<=', now()->subMinutes(10));
                });
            })
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get()
            ->each(function (OutboxEvent $event) use (&$count): void {
                $claimed = OutboxEvent::query()->whereKey($event->id)->whereIn('status', ['pending', 'processing'])->update(['status' => 'processing', 'updated_at' => now()]);
                if ($claimed === 1) {
                    ProcessOutboxEvent::dispatch($event->id);
                    $count++;
                }
            });

        $this->info("Dispatched {$count} outbox event(s).");
        return self::SUCCESS;
    }
}
}
