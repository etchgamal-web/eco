<?php

namespace App\Modules\Payment\Infrastructure\Persistence;

use App\Models\OutboxEvent;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\ProviderCircuitBreaker;
use App\Modules\Payment\Domain\Contracts\OperationalDashboardReaderInterface;
use Illuminate\Support\Facades\DB;

final class EloquentOperationalDashboardReader implements OperationalDashboardReaderInterface
{
    public function read(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'payments' => Payment::query()->select('status', DB::raw('count(*) as count'))->groupBy('status')->pluck('count', 'status'),
            'webhooks' => PaymentWebhookEvent::query()->select('status', DB::raw('count(*) as count'))->groupBy('status')->pluck('count', 'status'),
            'outbox' => OutboxEvent::query()->select('status', DB::raw('count(*) as count'))->groupBy('status')->pluck('count', 'status'),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'circuits' => ProviderCircuitBreaker::query()->get(['provider', 'failure_count', 'opened_until']),
        ];
    }
}
