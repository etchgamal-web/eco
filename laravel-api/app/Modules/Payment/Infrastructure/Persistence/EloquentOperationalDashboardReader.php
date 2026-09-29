<?php

namespace App\Modules\Payment\Infrastructure\Persistence;

use App\Modules\Payment\Domain\Contracts\OperationalDashboardReaderInterface;
use App\Modules\Payment\Infrastructure\Models\Payment;
use App\Modules\Payment\Infrastructure\Models\PaymentWebhookEvent;
use App\Modules\Payment\Infrastructure\Models\ProviderCircuitBreaker;
use App\Shared\Domain\Contracts\OutboxRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class EloquentOperationalDashboardReader implements OperationalDashboardReaderInterface
{
    public function __construct(private readonly OutboxRepositoryInterface $outbox) {}

    public function read(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'payments' => Payment::query()->select('status', DB::raw('count(*) as count'))->groupBy('status')->pluck('count', 'status'),
            'webhooks' => PaymentWebhookEvent::query()->select('status', DB::raw('count(*) as count'))->groupBy('status')->pluck('count', 'status'),
            'outbox' => $this->outbox->countByStatus(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'circuits' => ProviderCircuitBreaker::query()->get(['provider', 'failure_count', 'opened_until']),
        ];
    }
}
