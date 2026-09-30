<?php

namespace App\Console\Commands;

use App\Modules\Payment\Infrastructure\Models\PaymentOperation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class AlertAmbiguousRefunds extends Command
{
    protected $signature = 'payments:alert-ambiguous-refunds {--minutes= : Minimum age of an ambiguous refund before alerting}';

    protected $description = 'Alert when provider refund reconciliation remains ambiguous beyond the configured threshold';

    public function handle(): int
    {
        $minutes = (int) ($this->option('minutes') ?: config('payments.refund_ambiguous_alert_minutes', 60));
        $cutoff = now()->subMinutes(max(1, $minutes));
        $operations = PaymentOperation::query()
            ->with('payment:id,provider_reference,method')
            ->where('operation', 'refund')
            ->where('status', 'ambiguous')
            ->where(function ($query) use ($cutoff): void {
                $query->whereNull('last_reconciliation_at')->orWhere('last_reconciliation_at', '<=', $cutoff);
            })
            ->orderBy('last_reconciliation_at')
            ->limit(100)
            ->get();

        foreach ($operations as $operation) {
            Log::warning('Refund reconciliation remains ambiguous beyond the alert threshold.', [
                'payment_id' => $operation->payment_id,
                'operation_id' => $operation->id,
                'provider' => $operation->payment?->method,
                'provider_reference' => $operation->payment?->provider_reference,
                'last_reconciliation_at' => $operation->last_reconciliation_at?->toIso8601String(),
                'next_reconciliation_at' => $operation->next_reconciliation_at?->toIso8601String(),
                'last_error' => $operation->last_error,
            ]);
        }

        $count = $operations->count();
        if ($count > 0) {
            $this->warn("{$count} ambiguous refund operation(s) exceeded the alert threshold.");
        } else {
            $this->info('No ambiguous refunds exceeded the alert threshold.');
        }

        return self::SUCCESS;
    }
}
