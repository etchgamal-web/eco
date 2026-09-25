<?php

namespace App\Modules\Order\Infrastructure\Persistence;

use App\Modules\Order\Domain\Contracts\ReturnRepositoryInterface;
use App\Modules\Order\Domain\Exceptions\ReturnException;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Order\Infrastructure\Models\OrderReturn;
use App\Modules\Shared\Domain\Contracts\OutboxEventRepositoryInterface;
use App\Modules\Staff\Infrastructure\Models\AuditLog;
use Illuminate\Support\Facades\DB;

final class EloquentReturnRepository implements ReturnRepositoryInterface
{
    public function __construct(private readonly OutboxEventRepositoryInterface $outbox) {}

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
                $refund += $orderItem->unit_price * $quantity;
                $items[] = ['order_item_id' => $orderItem->id, 'product_id' => $orderItem->product_id, 'quantity' => $quantity, 'unit_price' => $orderItem->unit_price];
            }
            if ($items === []) {
                throw ReturnException::invalidItems();
            }
            $return = OrderReturn::query()->create(['order_id' => $orderId, 'user_id' => $userId, 'status' => 'pending', 'reason' => $data['reason'], 'notes' => $data['notes'] ?? null, 'refund_amount' => $refund]);
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
            $this->outbox->record('order_return', (int) $return->id, 'order.return.approved', 'return:approved:'.$return->id, ['return_id' => $return->id, 'order_id' => $return->order_id, 'refund_amount' => $return->refund_amount]);
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
            $this->outbox->record('order_return', (int) $return->id, $event, 'return:inspection:'.$return->id, ['return_id' => $return->id, 'order_id' => $return->order_id, 'refund_amount' => $return->refund_amount]);
            AuditLog::query()->create(['actor_id' => auth()->id(), 'action' => $event, 'target_type' => OrderReturn::class, 'target_id' => $return->id, 'metadata' => ['notes' => $notes]]);

            return $return->fresh('items');
        });
    }
}
