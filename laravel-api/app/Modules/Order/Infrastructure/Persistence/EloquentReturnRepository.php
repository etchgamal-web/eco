<?php

namespace App\Modules\Order\Infrastructure\Persistence;

use App\Modules\Order\Domain\Contracts\ReturnRepositoryInterface;
use App\Modules\Order\Domain\Exceptions\ReturnException;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Order\Infrastructure\Models\OrderReturn;
use App\Shared\Domain\Contracts\OutboxRepositoryInterface;
use App\Shared\Domain\Data\OutboxMessage;
use App\Modules\Staff\Infrastructure\Models\AuditLog;
use Illuminate\Support\Facades\DB;

final class EloquentReturnRepository implements ReturnRepositoryInterface
{
    public function __construct(private readonly OutboxRepositoryInterface $outbox) {}

    public function createForCustomer(int $userId, int $orderId, array $data): object
    {
        return DB::transaction(function () use ($userId, $orderId, $data): object {
            $order = CustomerOrder::query()->with('items')->where('user_id', $userId)->find($orderId);
            if ($order === null || $order->status !== 'delivered') {
                throw ReturnException::notAllowed();
            }
            if (OrderReturn::query()->where('order_id', $orderId)->whereIn('status', ['pending', 'approved'])->exists()) {
                throw ReturnException::alreadyRequested();
            }
            $orderItems = $order->items->keyBy('id');
            $refund = 0;
            $items = [];
            foreach ($data['items'] as $item) {
                $orderItem = $orderItems->get((int) ($item['order_item_id'] ?? 0));
                $quantity = (int) ($item['quantity'] ?? 0);
                if ($orderItem === null || $quantity < 1 || $quantity > $orderItem->quantity) {
                    throw ReturnException::invalidItems();
                }
                $snapshotUnit = $orderItem->quantity > 0 && $orderItem->total_amount !== null
                    ? intdiv((int) $orderItem->total_amount, (int) $orderItem->quantity)
                    : (int) $orderItem->unit_price;
                $refund += $snapshotUnit * $quantity;
                $items[] = ['order_item_id' => $orderItem->id, 'product_id' => $orderItem->product_id, 'variant_id' => $orderItem->variant_id, 'quantity' => $quantity, 'unit_price' => $snapshotUnit];
            }
            if ($items === []) {
                throw ReturnException::invalidItems();
            }
            $payment = $order->payments()->whereIn('status', ['paid', 'confirmed'])->latest('id')->first();
            $return = OrderReturn::query()->create(['order_id' => $orderId, 'payment_id' => $payment?->id, 'user_id' => $userId, 'status' => 'pending', 'reason' => $data['reason'], 'notes' => $data['notes'] ?? null, 'refund_amount' => $refund]);
            $return->items()->createMany($items);

            return $return->load('items');
        });
    }

    public function listForCustomer(int $userId): iterable
    {
        return OrderReturn::query()->with('items')->where('user_id', $userId)->latest()->get();
    }

    public function listAll(): iterable
    {
        return OrderReturn::query()->with(['items', 'order:id,user_id,status', 'user:id,name'])->latest()->get();
    }

    public function approve(int $returnId): object
    {
        return DB::transaction(function () use ($returnId): object {
            $return = OrderReturn::query()->lockForUpdate()->find($returnId);
            if ($return === null || $return->status !== 'pending') {
                throw ReturnException::invalidTransition();
            }
            $return->update(['status' => 'approved']);
            $this->outbox->add(new OutboxMessage('order.return.approved', 'order_return', (int) $return->id, ['return_id' => $return->id, 'order_id' => $return->order_id, 'refund_amount' => $return->refund_amount], deduplicationKey: 'return:approved:'.$return->id));
            AuditLog::query()->create(['actor_id' => auth()->id(), 'action' => 'order.return.approved', 'target_type' => OrderReturn::class, 'target_id' => $return->id, 'metadata' => ['refund_amount' => $return->refund_amount]]);

            return $return->fresh('items');
        });
    }

    public function reject(int $returnId, string $reason): object
    {
        $return = OrderReturn::query()->find($returnId);
        if ($return === null || $return->status !== 'pending') {
            throw ReturnException::invalidTransition();
        }
        $return->update(['status' => 'rejected', 'rejection_reason' => $reason]);

        return $return->fresh('items');
    }

    public function findForWorkflow(int $returnId): object
    {
        return OrderReturn::query()->with(['items.orderItem', 'order.payments', 'payment'])->lockForUpdate()->findOrFail($returnId);
    }

    public function markRestocked(int $returnId): object
    {
        $return = OrderReturn::query()->lockForUpdate()->findOrFail($returnId);
        if ($return->restocked_at === null) $return->update(['restocked_at' => now()]);
        return $return->fresh(['items.orderItem', 'order.payments']);
    }

    public function markRefundRequested(int $returnId): object
    {
        $return = OrderReturn::query()->lockForUpdate()->findOrFail($returnId);
        if ($return->refund_requested_at === null) $return->update(['refund_requested_at' => now()]);
        return $return->fresh(['items.orderItem', 'order.payments', 'payment']);
    }

    public function markCompleted(int $returnId, int $actualRefund): object
    {
        $return = OrderReturn::query()->lockForUpdate()->findOrFail($returnId);
        if ($return->status !== 'completed') $return->update(['status' => 'completed', 'completed_at' => now(), 'actual_customer_refund' => $actualRefund]);
        return $return;
    }

    public function completeRefundForPayment(int $paymentId, int $actualRefund): void
    {
        OrderReturn::query()->where('payment_id', $paymentId)->where('status', 'inspected_accepted')->update(['status' => 'completed', 'completed_at' => now(), 'actual_customer_refund' => $actualRefund]);
    }

    public function receive(int $returnId): object
    {
        return DB::transaction(function () use ($returnId): object {
            $return = OrderReturn::query()->lockForUpdate()->find($returnId);
            if ($return === null || $return->status !== 'approved') {
                throw ReturnException::invalidTransition();
            }
            $return->update(['status' => 'received', 'received_at' => now()]);
            AuditLog::query()->create(['actor_id' => auth()->id(), 'action' => 'order.return.received', 'target_type' => OrderReturn::class, 'target_id' => $return->id]);

            return $return->fresh('items');
        });
    }

    public function inspect(int $returnId, bool $accepted, ?string $notes = null): object
    {
        return DB::transaction(function () use ($returnId, $accepted, $notes): object {
            $return = OrderReturn::query()->lockForUpdate()->find($returnId);
            if ($return === null || $return->status !== 'received') {
                throw ReturnException::invalidTransition();
            }
            $return->update(['status' => $accepted ? 'inspected_accepted' : 'inspected_rejected', 'inspected_at' => now(), 'inspection_notes' => $notes]);
            $event = $accepted ? 'order.return.inspection.accepted' : 'order.return.inspection.rejected';
            $this->outbox->add(new OutboxMessage($event, 'order_return', (int) $return->id, ['return_id' => $return->id, 'order_id' => $return->order_id, 'refund_amount' => $return->refund_amount], deduplicationKey: 'return:inspection:'.$return->id));
            AuditLog::query()->create(['actor_id' => auth()->id(), 'action' => $event, 'target_type' => OrderReturn::class, 'target_id' => $return->id, 'metadata' => ['notes' => $notes]]);

            return $return->fresh('items');
        });
    }
}
