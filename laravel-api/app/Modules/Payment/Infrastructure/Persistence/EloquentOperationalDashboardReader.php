<?php

namespace App\Modules\Payment\Infrastructure\Persistence;

use App\Modules\Payment\Domain\Contracts\OperationalDashboardReaderInterface;
use App\Modules\Payment\Infrastructure\Models\Payment;
use App\Modules\Payment\Infrastructure\Models\PaymentWebhookEvent;
use App\Modules\Payment\Infrastructure\Models\ProviderCircuitBreaker;
use App\Modules\Shared\Infrastructure\Models\OutboxEvent;
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
