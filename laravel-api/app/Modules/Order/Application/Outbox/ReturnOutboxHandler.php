<?php

namespace App\Modules\Order\Application\Outbox;

use App\Modules\Inventory\Domain\Contracts\InventoryRepositoryInterface;
use App\Modules\Inventory\Domain\ValueObjects\StockAdjustmentData;
use App\Modules\Order\Domain\Contracts\ReturnRepositoryInterface;
use App\Modules\Payment\Application\UseCases\RefundPayment;
use App\Modules\Shared\Domain\Contracts\TransactionManagerInterface;
use App\Shared\Domain\Contracts\OutboxEventHandlerInterface;
use App\Shared\Domain\Contracts\OutboxRepositoryInterface;

final class ReturnOutboxHandler implements OutboxEventHandlerInterface
{
    public function __construct(
        private readonly ReturnRepositoryInterface $returns,
        private readonly InventoryRepositoryInterface $inventory,
        private readonly RefundPayment $refunds,
        private readonly OutboxRepositoryInterface $outbox,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    public function supports(string $eventType): bool
    {
        return in_array($eventType, ['order.return.approved', 'order.return.inspection.accepted', 'order.return.inspection.rejected'], true);
    }

    public function handle(object $event): void
    {
        $returnId = (int) $event->aggregate_id;
        if ($event->event_type !== 'order.return.inspection.accepted') {
            $this->outbox->markProcessed((int) $event->id, (string) $event->claim_token);
            return;
        }

        $return = $this->transactions->run(function () use ($returnId): object {
            $return = $this->returns->findForWorkflow($returnId);
            if ($return->status === 'completed') return $return;
            if ($return->restocked_at === null) {
                foreach ($return->items as $item) {
                    if ($item->product_id === null) continue;
                    $variantId = $item->variant_id ?? $item->orderItem?->variant_id;
                    $this->inventory->adjust(new StockAdjustmentData((int) $item->product_id, $variantId !== null ? (int) $variantId : null, (int) $item->quantity, 'return_restock', 'Return #'.$return->id.' inspection accepted'), null);
                }
                $return = $this->returns->markRestocked($returnId);
            }
            return $return;
        });

        $payment = $return->payment ?: $return->order->payments->whereIn('status', ['paid', 'confirmed', 'refunded'])->sortByDesc('id')->first();
        if ($payment === null) throw new \RuntimeException('No refundable payment found for accepted return.');
        $this->transactions->run(fn (): object => $this->returns->markRefundRequested($returnId));
        if ($payment->status !== 'refunded') {
            $this->refunds->execute((int) $payment->id, (int) $return->refund_amount);
        }

        $payment = $payment->fresh();
        $actualRefund = (int) data_get($payment->metadata, 'refund_confirmed_amount', $return->refund_amount);
        $this->transactions->run(fn (): object => $this->returns->markCompleted($returnId, $actualRefund));
        $this->outbox->markProcessed((int) $event->id, (string) $event->claim_token);
    }

    public function failed(object $event, \Throwable $exception): void
    {
        $this->outbox->markFailed((int) $event->id, (string) $event->claim_token, $exception->getMessage());
    }
}
