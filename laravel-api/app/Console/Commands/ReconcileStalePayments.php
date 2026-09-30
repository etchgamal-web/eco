<?php

namespace App\Console\Commands;

use App\Modules\Payment\Application\UseCases\ReconcilePayment;
use App\Modules\Payment\Infrastructure\Models\Payment;
use App\Modules\Payment\Infrastructure\Models\PaymentOperation;
use Illuminate\Console\Command;

if (! class_exists(__NAMESPACE__.'\\ReconcileStalePayments', false)) {
    final class ReconcileStalePayments extends Command
    {
        protected $signature = 'payments:reconcile {--minutes=5 : Minimum age of a non-terminal payment}';

        protected $description = 'Reconcile stale processing and provider-created payments with their provider';

        public function handle(): int
        {
            $count = 0;
            $cutoff = now()->subMinutes((int) $this->option('minutes'));
            PaymentOperation::query()->where('status', 'ambiguous')
                ->where(function ($query): void {
                    $query->whereNull('next_reconciliation_at')->orWhere('next_reconciliation_at', '<=', now());
                })
                ->whereHas('payment', fn ($query) => $query->where('updated_at', '<=', $cutoff))
                ->orderBy('id')->limit(100)->get(['id', 'payment_id'])->each(function (PaymentOperation $operation) use (&$count): void {
                    try {
                        app(ReconcilePayment::class)->execute((int) $operation->payment_id, (int) $operation->id);
                        $count++;
                    } catch (\Throwable $exception) {
                        report($exception);
                    }
                });

            $ambiguousPaymentIds = PaymentOperation::query()->where('status', 'ambiguous')->pluck('payment_id');
            Payment::query()->whereIn('status', ['processing', 'provider_created'])->whereNotIn('id', $ambiguousPaymentIds)
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
