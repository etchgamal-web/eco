<?php

namespace App\Modules\Order\Infrastructure\Persistence;

use App\Models\Coupon;
use App\Models\CustomerOrder;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\User;
use App\Modules\Inventory\Domain\Contracts\InventoryRepositoryInterface;
use App\Modules\Order\Domain\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Domain\Exceptions\CheckoutException;
use App\Modules\Order\Domain\Exceptions\InvalidShippingChargeException;
use App\Modules\Order\Domain\Exceptions\OrderActionNotAllowedException;
use App\Modules\Order\Domain\Exceptions\OrderNotFoundException;
use App\Modules\Order\Domain\StateMachines\OrderStateMachine;
use App\Modules\Promotion\Domain\Contracts\CouponServiceInterface;
use App\Modules\Tax\Domain\Contracts\TaxCalculatorInterface;
use Illuminate\Support\Facades\DB;

final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventory,
        private readonly CouponServiceInterface $coupons,
        private readonly TaxCalculatorInterface $taxes,
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

    public function checkout(int $userId, int $addressId, string $currency, ?string $idempotencyKey, ?string $couponCode = null): object
    {
        return DB::transaction(function () use ($userId, $addressId, $currency, $idempotencyKey, $couponCode): object {
            if ($idempotencyKey !== null) {
                $existing = CustomerOrder::query()->where('idempotency_key', $idempotencyKey)->first();
                if ($existing !== null) {
                    if ((int) $existing->user_id !== $userId) {
                        throw CheckoutException::idempotencyKeyConflict();
                    }

                    return $existing->load('items');
                }
            }
            $user = User::query()->with(['cart.items.product', 'cart.items.variant', 'addresses'])->findOrFail($userId);
            $cart = $user->cart;
            $items = $cart?->items ?? collect();
            if ($items->isEmpty()) {
                throw CheckoutException::emptyCart();
            }
            $address = $user->addresses()->findOrFail($addressId);
            $subtotal = 0;
            $snapshots = [];
            foreach ($items as $cartItem) {
                $product = $cartItem->product;
                if ($product === null || $product->status !== 'active') {
                    throw CheckoutException::unavailableProduct($product?->name ?? 'unknown');
                }
                $variant = $cartItem->variant;
                if ($cartItem->quantity <= 0) {
                    throw CheckoutException::emptyCart();
                }
                if ($product->type === 'variable' && ($variant === null || $variant->status !== 'active')) {
                    throw CheckoutException::unavailableProduct($product->name);
                }
                $unitPrice = $variant?->price ?? $product->price;
                if ($unitPrice === null || $unitPrice < 0) {
                    throw CheckoutException::missingPrice($product->name);
                }
                $lineTotal = $unitPrice * $cartItem->quantity;
                $subtotal += $lineTotal;
                $snapshots[] = ['product_id' => $product->id, 'variant_id' => $variant?->id, 'name' => $product->name, 'sku' => $variant?->sku, 'quantity' => $cartItem->quantity, 'unit_price' => $unitPrice, 'discount_amount' => 0, 'tax_amount' => 0, 'total_amount' => $lineTotal];
                $this->inventory->reserve($product->id, $variant?->id, $cartItem->quantity);
            }
            $promotion = $this->coupons->apply($couponCode, $userId, $subtotal);
            $tax = $this->taxes->calculate($subtotal - $promotion['discount'], (string) $address->country, $address->state);
            $total = $subtotal - $promotion['discount'] + $tax['amount'];
            $order = CustomerOrder::query()->create(['user_id' => $userId, 'status' => 'pending', 'total_amount' => $total, 'subtotal_amount' => $subtotal, 'discount_amount' => $promotion['discount'], 'coupon_code' => $promotion['code'], 'tax_amount' => $tax['amount'], 'tax_rate' => $tax['rate'], 'tax_rule_id' => $tax['rule_id'], 'shipping_amount' => 0, 'currency' => $currency, 'shipping_address' => ['recipient_name' => $address->recipient_name, 'phone' => $address->phone, 'address_line1' => $address->address_line1, 'address_line2' => $address->address_line2, 'city' => $address->city, 'state' => $address->state, 'postal_code' => $address->postal_code, 'country' => $address->country], 'idempotency_key' => $idempotencyKey]);
            $order->items()->createMany($snapshots);
            if ($promotion['code'] !== null) {
                $coupon = Coupon::query()->where('code', $promotion['code'])->firstOrFail();
                $coupon->usages()->create(['user_id' => $userId, 'order_id' => $order->id, 'discount_amount' => $promotion['discount']]);
            }
            $cart->items()->delete();

            return $order->load('items');
        });
    }

    public function checkoutGuest(array $items, array $details, string $currency, ?string $idempotencyKey, ?string $couponCode = null): object
    {
        return DB::transaction(function () use ($items, $details, $currency, $idempotencyKey): object {
            if ($idempotencyKey !== null) {
                $existing = CustomerOrder::query()->where('idempotency_key', $idempotencyKey)->first();
                if ($existing !== null) {
                    return $existing->load('items');
                }
            }

            $subtotal = 0;
            $snapshots = [];
            foreach ($items as $item) {
                $product = Product::query()->with('variants')->find($item['product_id']);
                if ($product === null || $product->status !== 'active') {
                    throw CheckoutException::unavailableProduct((string) ($product?->name ?? 'unknown'));
                }
                $variant = isset($item['variant_id']) ? $product->variants->firstWhere('id', (int) $item['variant_id']) : null;
                if ($product->type === 'variable' && ($variant === null || $variant->status !== 'active')) {
                    throw CheckoutException::unavailableProduct($product->name);
                }
                $quantity = (int) $item['quantity'];
                $unitPrice = $variant?->price ?? $product->price;
                if ($quantity < 1) {
                    throw CheckoutException::emptyCart();
                }
                if ($unitPrice === null || $unitPrice < 0) {
                    throw CheckoutException::missingPrice($product->name);
                }
                $lineTotal = $unitPrice * $quantity;
                $subtotal += $lineTotal;
                $snapshots[] = ['product_id' => $product->id, 'variant_id' => $variant?->id, 'name' => $product->name, 'sku' => $variant?->sku, 'quantity' => $quantity, 'unit_price' => $unitPrice, 'discount_amount' => 0, 'tax_amount' => 0, 'total_amount' => $lineTotal];
                $this->inventory->reserve($product->id, $variant?->id, $quantity);
            }

            $country = strtoupper((string) ($details['country'] ?? 'EG'));
            $tax = $this->taxes->calculate($subtotal, $country, $details['state'] ?? null);
            $total = $subtotal + $tax['amount'];
            $order = CustomerOrder::query()->create([
                'user_id' => null,
                'guest_email' => $details['email'] ?? null,
                'guest_phone' => $details['phone'],
                'status' => 'pending',
                'total_amount' => $total,
                'subtotal_amount' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => $tax['amount'],
                'tax_rate' => $tax['rate'],
                'tax_rule_id' => $tax['rule_id'],
                'shipping_amount' => 0,
                'currency' => $currency,
                'shipping_address' => ['recipient_name' => $details['name'], 'phone' => $details['phone'], 'address_line1' => $details['address_line1'], 'address_line2' => $details['address_line2'] ?? null, 'city' => $details['city'], 'state' => $details['state'] ?? null, 'postal_code' => $details['postal_code'] ?? null, 'country' => $country],
                'idempotency_key' => $idempotencyKey,
            ]);
            $order->items()->createMany($snapshots);

            return $order->load('items');
        });
    }
}
