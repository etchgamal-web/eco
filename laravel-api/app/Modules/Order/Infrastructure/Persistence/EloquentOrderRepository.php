<?php

namespace App\Modules\Order\Infrastructure\Persistence;

use App\Modules\Inventory\Domain\Contracts\InventoryRepositoryInterface;
use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Domain\Exceptions\InvalidShippingChargeException;
use App\Modules\Order\Domain\Exceptions\OrderActionNotAllowedException;
use App\Modules\Order\Domain\Exceptions\OrderNotFoundException;
use App\Modules\Order\Domain\StateMachines\OrderStateMachine;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Shipping\Infrastructure\Models\Shipment;
use Illuminate\Support\Facades\DB;

final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventory,
    ) {}

    public function listForUser(int $userId): iterable
    {
        return CustomerOrder::query()->with(['items.product', 'review'])->where('user_id', $userId)->latest()->get();
    }

    public function listAll(): iterable
    {
        return CustomerOrder::query()->with(['user', 'items.product', 'review.reviewer', 'review.confirmer'])->latest()->get();
    }

    public function findForUser(int $userId, int $orderId): object
    {
        $order = CustomerOrder::query()->with(['items.product', 'review.reviewer', 'review.confirmer'])->where('user_id', $userId)->find($orderId);
        if ($order === null) {
            throw new OrderNotFoundException('Order not found.');
        }

        return $order;
    }

    public function find(int $orderId): object
    {
        $order = CustomerOrder::query()->with(['user', 'items.product', 'review.reviewer', 'review.confirmer'])->find($orderId);
        if ($order === null) {
            throw new OrderNotFoundException('Order not found.');
        }

        return $order;
    }

    public function updateStatus(int $orderId, string $status): object
    {
        return DB::transaction(function () use ($orderId, $status): object {
            $order = CustomerOrder::query()->lockForUpdate()->find($orderId);
            if ($order === null) {
                throw new OrderNotFoundException('Order not found.');
            }
            if (in_array($status, ['reviewing', 'confirmed'], true)) {
                throw new OrderActionNotAllowedException('Use the dedicated order workflow endpoint for this transition.');
            }
            OrderStateMachine::assert((string) $order->status, $status);
            if ($status === 'shipped') {
                $hasReadyShipment = Shipment::query()->where('order_id', $order->id)
                    ->whereIn('status', ['picked_up', 'in_transit', 'out_for_delivery', 'delivered'])
                    ->where('creation_status', 'created')
                    ->exists();
                if (! $hasReadyShipment) {
                    throw new OrderActionNotAllowedException('Order cannot be shipped without a successfully created shipment.');
                }
            }
            if ($status === 'delivered' && ! Shipment::query()->where('order_id', $order->id)->where('status', 'delivered')->exists()) {
                throw new OrderActionNotAllowedException('Order cannot be delivered without a delivered shipment.');
            }
            if ($status === 'shipped') {
                foreach ($order->items as $item) {
                    $this->inventory->commit($item->product_id, $item->variant_id, $item->quantity);
                }
            }
            $order->update(['status' => $status]);

            return $order->fresh(['user', 'items.product', 'review.reviewer', 'review.confirmer']);
        });
    }

    public function cancelForUser(int $userId, int $orderId): object
    {
        return DB::transaction(function () use ($userId, $orderId): object {
            $order = CustomerOrder::query()->where('user_id', $userId)->lockForUpdate()->find($orderId);
            if ($order === null) {
                throw new OrderNotFoundException('Order not found.');
            }
            if (! in_array($order->status, ['pending', 'confirmed', 'processing'], true)) {
                throw new OrderActionNotAllowedException('This order can no longer be cancelled.');
            }
            foreach ($order->items as $item) {
                $this->inventory->release($item->product_id, $item->variant_id, $item->quantity);
            }
            $order->update(['status' => 'cancelled']);

            return $order->fresh(['items.product', 'items.variant']);
        });
    }

    public function cancel(int $orderId): object
    {
        return DB::transaction(function () use ($orderId): object {
            $order = CustomerOrder::query()->lockForUpdate()->find($orderId);
            if ($order === null) {
                throw new OrderNotFoundException('Order not found.');
            }
            if (! in_array($order->status, ['pending', 'confirmed', 'processing'], true)) {
                throw new OrderActionNotAllowedException('This order can no longer be cancelled.');
            }
            foreach ($order->items as $item) {
                $this->inventory->release($item->product_id, $item->variant_id, $item->quantity);
            }
            $order->update(['status' => 'cancelled']);

            return $order->fresh(['items.product', 'items.variant']);
        });
    }

    public function addShippingFee(int $orderId, int $fee): object
    {
        return DB::transaction(function () use ($orderId, $fee): CustomerOrder {
            $order = CustomerOrder::query()->lockForUpdate()->find($orderId);
            if ($order === null) {
                throw new OrderNotFoundException('Order not found.');
            }
            $shippingAmount = $order->shipping_amount + $fee;
            $this->applyShippingAmounts($order, $shippingAmount, (int) $order->shipping_cost);

            return $order->fresh(['items.product', 'items.variant', 'payments', 'shipments']);
        });
    }

    public function setShippingCharge(int $orderId, int $customerShippingAmount): object
    {
        if ($customerShippingAmount < 0) {
            throw new \InvalidArgumentException('Customer shipping amount cannot be negative.');
        }

        return DB::transaction(function () use ($orderId, $customerShippingAmount): CustomerOrder {
            $order = CustomerOrder::query()->lockForUpdate()->find($orderId);
            if ($order === null) {
                throw new OrderNotFoundException('Order not found.');
            }
            $this->applyShippingAmounts($order, $customerShippingAmount, (int) $order->shipping_cost);

            return $order->fresh(['items.product', 'items.variant', 'payments', 'shipments']);
        });
    }

    public function setShippingCost(int $orderId, int $shippingCost): object
    {
        if ($shippingCost < 0) {
            throw new \InvalidArgumentException('Shipping cost cannot be negative.');
        }

        return DB::transaction(function () use ($orderId, $shippingCost): CustomerOrder {
            $order = CustomerOrder::query()->lockForUpdate()->find($orderId);
            if ($order === null) {
                throw new OrderNotFoundException('Order not found.');
            }
            $this->applyShippingAmounts($order, (int) $order->shipping_amount, $shippingCost);

            return $order->fresh(['items.product', 'items.variant', 'payments', 'shipments']);
        });
    }

    private function applyShippingAmounts(CustomerOrder $order, int $customerShippingAmount, int $shippingCost): void
    {
        if ($customerShippingAmount > $shippingCost) {
            throw new InvalidShippingChargeException('Customer shipping amount cannot exceed carrier shipping cost.');
        }

        $subtotal = $order->subtotal_amount ?? ((int) $order->total_amount - (int) $order->shipping_amount);
        $order->update([
            'shipping_amount' => $customerShippingAmount,
            'shipping_cost' => $shippingCost,
            'shipping_subsidy' => $shippingCost - $customerShippingAmount,
            'total_amount' => $subtotal - (int) $order->discount_amount + (int) $order->tax_amount + $customerShippingAmount,
        ]);
    }

    public function markRefunded(int $orderId): object
    {
        return DB::transaction(function () use ($orderId): object {
            $order = CustomerOrder::query()->lockForUpdate()->find($orderId);
            if ($order === null) {
                throw new OrderNotFoundException('Order not found.');
            }
            OrderStateMachine::assert((string) $order->status, 'refunded');
            $order->update(['status' => 'refunded']);

            return $order->fresh(['items.product']);
        });
    }
}
