<?php

namespace App\Modules\Order\Infrastructure\Persistence;

use App\Modules\Order\Domain\Contracts\OrderReviewRepositoryInterface;
use App\Modules\Order\Domain\Exceptions\OrderActionNotAllowedException;
use App\Modules\Order\Domain\Exceptions\OrderNotFoundException;
use App\Modules\Order\Domain\Exceptions\OrderReviewException;
use App\Modules\Order\Domain\StateMachines\OrderStateMachine;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Order\Infrastructure\Models\OrderReview;
use Illuminate\Support\Facades\DB;

final class EloquentOrderReviewRepository implements OrderReviewRepositoryInterface
{
    public function findForOrder(int $orderId): ?object
    {
        return OrderReview::query()->with(['reviewer', 'confirmer'])->where('order_id', $orderId)->first();
    }

    public function start(int $orderId, int $reviewerId): object
    {
        return DB::transaction(function () use ($orderId, $reviewerId): object {
            $order = CustomerOrder::query()->lockForUpdate()->find($orderId);
            if ($order === null) {
                throw new OrderNotFoundException('Order not found.');
            }
            OrderStateMachine::assert((string) $order->status, 'reviewing');
            if (OrderReview::query()->where('order_id', $orderId)->exists()) {
                throw new OrderActionNotAllowedException('This order has already entered review.');
            }
            $order->update(['status' => 'reviewing']);

            return OrderReview::query()->create([
                'order_id' => $orderId,
                'reviewer_id' => $reviewerId,
                'started_at' => now(),
            ])->load(['reviewer', 'confirmer', 'order']);
        });
    }

    public function recordContact(int $orderId, string $contactResult, ?string $notes): object
    {
        return DB::transaction(function () use ($orderId, $contactResult, $notes): object {
            $order = CustomerOrder::query()->lockForUpdate()->find($orderId);
            if ($order === null) {
                throw new OrderNotFoundException('Order not found.');
            }
            if ($order->status !== 'reviewing') {
                throw new OrderActionNotAllowedException('Contact can only be recorded while the order is under review.');
            }
            $review = OrderReview::query()->where('order_id', $orderId)->lockForUpdate()->first();
            if ($review === null) {
                throw OrderReviewException::notFound();
            }
            $review->update(['contacted_at' => now(), 'contact_result' => $contactResult, 'notes' => $notes]);

            return $review->fresh(['reviewer', 'confirmer', 'order']);
        });
    }

    public function confirm(int $orderId, int $confirmedBy): object
    {
        return DB::transaction(function () use ($orderId, $confirmedBy): object {
            $order = CustomerOrder::query()->lockForUpdate()->find($orderId);
            if ($order === null) {
                throw new OrderNotFoundException('Order not found.');
            }
            OrderStateMachine::assert((string) $order->status, 'confirmed');
            $review = OrderReview::query()->where('order_id', $orderId)->lockForUpdate()->first();
            if ($review === null || $review->contacted_at === null || $review->contact_result !== 'confirmed') {
                throw OrderReviewException::contactRequired();
            }
            $order->update(['status' => 'confirmed']);
            $review->update(['confirmed_at' => now(), 'confirmed_by' => $confirmedBy]);

            return $review->fresh(['reviewer', 'confirmer', 'order']);
        });
    }
}
