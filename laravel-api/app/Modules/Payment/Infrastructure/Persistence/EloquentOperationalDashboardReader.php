<?php

namespace App\Modules\Payment\Infrastructure\Persistence;

use App\Modules\Payment\Domain\Contracts\OperationalDashboardReaderInterface;
use App\Modules\Payment\Infrastructure\Models\Payment;
use App\Modules\Payment\Infrastructure\Models\PaymentOperation;
use App\Modules\Payment\Infrastructure\Models\PaymentWebhookEvent;
use App\Modules\Payment\Infrastructure\Models\ProviderCircuitBreaker;
use App\Modules\Order\Infrastructure\Models\OrderReturn;
use App\Shared\Infrastructure\Outbox\Models\OutboxEvent;
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
            'circuits' => ProviderCircuitBreaker::query()->get(['provider', 'failure_count', 'opened_until'])->map(fn ($circuit) => array_merge($circuit->toArray(), ['is_open' => $circuit->opened_until !== null && $circuit->opened_until->isFuture()]))->values(),
            'ambiguous_payments' => Payment::query()->where('status', 'ambiguous')->with('order:id,order_number')->latest('updated_at')->limit(100)->get(['id', 'order_id', 'method', 'provider_reference', 'amount', 'updated_at']),
            'ambiguous_refunds' => PaymentOperation::query()->with('payment:id,method,provider_reference')->where('operation', 'refund')->where('status', 'ambiguous')->orderBy('next_reconciliation_at')->limit(100)->get(['id', 'payment_id', 'last_error', 'last_reconciliation_at', 'next_reconciliation_at']),
            'stuck_returns' => OrderReturn::query()->where('status', 'inspected_accepted')->where(function ($query): void {
                $query->where('restock_status', '!=', 'completed')->orWhere('refund_status', '!=', 'refunded');
            })->latest('updated_at')->limit(100)->get(['id', 'order_id', 'payment_id', 'status', 'restock_status', 'refund_status', 'workflow_error', 'last_workflow_attempt_at', 'refund_amount']),
            'failed_outbox' => OutboxEvent::query()->where('status', 'failed')->latest('updated_at')->limit(100)->get(['id', 'aggregate_type', 'aggregate_id', 'event_type', 'attempt_count', 'last_error', 'updated_at']),
        ];
    }
}
