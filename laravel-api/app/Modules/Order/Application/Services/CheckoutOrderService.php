<?php

namespace App\Modules\Order\Application\Services;

use App\Modules\Inventory\Domain\Contracts\InventoryRepositoryInterface;
use App\Modules\Order\Domain\Contracts\CheckoutGatewayInterface;
use App\Modules\Order\Domain\Exceptions\CheckoutException;
use App\Modules\Order\Domain\ValueObjects\CheckoutData;
use App\Modules\Promotion\Domain\Contracts\CouponServiceInterface;
use App\Modules\Shared\Domain\Contracts\TransactionManagerInterface;
use App\Modules\Tax\Domain\Contracts\TaxCalculatorInterface;

final class CheckoutOrderService
{
    public function __construct(
        private readonly CheckoutGatewayInterface $gateway,
        private readonly InventoryRepositoryInterface $inventory,
        private readonly CouponServiceInterface $coupons,
        private readonly TaxCalculatorInterface $taxes,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    public function execute(CheckoutData $data, ?int $userId): object
    {
        return $this->transactions->run(function () use ($data, $userId): object {
            if ($data->idempotencyKey !== null) {
                $existing = $this->gateway->findByIdempotencyKey($data->idempotencyKey);
                if ($existing !== null) {
                    if ($userId !== null && (int) $existing->user_id !== $userId) {
                        throw CheckoutException::idempotencyKeyConflict();
                    }

                    return $existing->load('items');
                }
            }

            return $userId === null
                ? $this->checkoutGuest($data)
                : $this->checkoutCustomer($data, $userId);
        });
    }

    private function checkoutCustomer(CheckoutData $data, int $userId): object
    {
        $user = $this->gateway->customerContext($userId);
        $cart = $user->cart;
        $items = $cart?->items;
        if ($items === null || $items->isEmpty()) {
            throw CheckoutException::emptyCart();
        }

        $address = $user->addresses()->findOrFail($data->addressId);
        [$subtotal, $snapshots] = $this->priceCustomerItems($items);
        $promotion = $this->coupons->apply($data->couponCode, $userId, $subtotal);
        $tax = $this->taxes->calculate($subtotal - $promotion['discount'], (string) $address->country, $address->state);

        $order = $this->gateway->createOrder([
            'user_id' => $userId,
            'status' => 'pending',
            'total_amount' => $subtotal - $promotion['discount'] + $tax['amount'],
            'subtotal_amount' => $subtotal,
            'discount_amount' => $promotion['discount'],
            'coupon_code' => $promotion['code'],
            'tax_amount' => $tax['amount'],
            'tax_rate' => $tax['rate'],
            'tax_rule_id' => $tax['rule_id'],
            'shipping_amount' => 0,
            'currency' => $data->currency,
            'shipping_address' => [
                'recipient_name' => $address->recipient_name,
                'phone' => $address->phone,
                'address_line1' => $address->address_line1,
                'address_line2' => $address->address_line2,
                'city' => $address->city,
                'state' => $address->state,
                'postal_code' => $address->postal_code,
                'country' => $address->country,
            ],
            'idempotency_key' => $data->idempotencyKey,
        ]);
        $this->gateway->createOrderItems($order, $snapshots);
        if ($promotion['code'] !== null) {
            $this->gateway->recordCouponUsage($promotion['code'], $userId, (int) $order->id, $promotion['discount']);
        }
        $this->gateway->clearCart($cart);

        return $order->load('items');
    }

    private function checkoutGuest(CheckoutData $data): object
    {
        $subtotal = 0;
        $snapshots = [];
        foreach ($data->guestItems as $item) {
            $product = $this->gateway->productForGuest((int) $item['product_id']);
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
            $snapshots[] = [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'name' => $product->name,
                'sku' => $variant?->sku,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $lineTotal,
            ];
            $this->inventory->reserve($product->id, $variant?->id, $quantity);
        }

        $details = $data->guestDetails;
        $country = strtoupper((string) ($details['country'] ?? 'EG'));
        $tax = $this->taxes->calculate($subtotal, $country, $details['state'] ?? null);
        $order = $this->gateway->createOrder([
            'user_id' => null,
            'guest_email' => $details['email'] ?? null,
            'guest_phone' => $details['phone'],
            'status' => 'pending',
            'total_amount' => $subtotal + $tax['amount'],
            'subtotal_amount' => $subtotal,
            'discount_amount' => 0,
            'tax_amount' => $tax['amount'],
            'tax_rate' => $tax['rate'],
            'tax_rule_id' => $tax['rule_id'],
            'shipping_amount' => 0,
            'currency' => $data->currency,
            'shipping_address' => [
                'recipient_name' => $details['name'],
                'phone' => $details['phone'],
                'address_line1' => $details['address_line1'],
                'address_line2' => $details['address_line2'] ?? null,
                'city' => $details['city'],
                'state' => $details['state'] ?? null,
                'postal_code' => $details['postal_code'] ?? null,
                'country' => $country,
            ],
            'idempotency_key' => $data->idempotencyKey,
        ]);
        $this->gateway->createOrderItems($order, $snapshots);

        return $order->load('items');
    }

    private function priceCustomerItems(iterable $items): array
    {
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
            $snapshots[] = [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'name' => $product->name,
                'sku' => $variant?->sku,
                'quantity' => $cartItem->quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $lineTotal,
            ];
            $this->inventory->reserve($product->id, $variant?->id, $cartItem->quantity);
        }

        return [$subtotal, $snapshots];
    }
}
