<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Modules\Payment\Application\UseCases\ReconcilePayment;
use Illuminate\Console\Command;

if (! class_exists(__NAMESPACE__ . '\\ReconcileStalePayments', false)) {
final class ReconcileStalePayments extends Command
{
    protected $signature = 'payments:reconcile {--minutes=5 : Minimum age of a non-terminal payment}';
    protected $description = 'Reconcile stale processing and provider-created payments with their provider';

    public function handle(): int
    {
        $count = 0;
        Payment::query()->whereIn('status', ['processing', 'provider_created'])
            ->where('updated_at', '<=', now()->subMinutes((int) $this->option('minutes')))
            ->orderBy('id')->limit(100)->pluck('id')->each(function (int $paymentId) use (&$count): void {
                try {
                    app(ReconcilePayment::class)->execute($paymentId);
                    $count++;
                } catch (\Throwable $exception) {
                    report($exception);
                }
            });
        $this->info("Reconciled {$count} payment(s).");
        return self::SUCCESS;
    }
}
}
