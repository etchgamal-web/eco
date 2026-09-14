<?php

namespace App\Modules\Customer\Infrastructure\Persistence;

use App\Models\CustomerCart;
use App\Models\CustomerCartItem;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Modules\Customer\Domain\Contracts\CartRepositoryInterface;
use App\Modules\Customer\Domain\Exceptions\CartItemNotFoundException;
use App\Modules\Customer\Domain\Exceptions\CartItemOutOfStockException;
use App\Modules\Customer\Domain\Exceptions\ProductNotPurchasableException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EloquentCartRepository implements CartRepositoryInterface
{
    public function get(int $userId): CustomerCart
    {
        $cart = CustomerCart::query()->firstOrCreate(
            ['user_id' => $userId],
            ['last_activity_at' => now(), 'recovery_token' => Str::random(64)]
        );
        if ($cart->recovery_token === null) $cart->update(['recovery_token' => Str::random(64)]);
        return $this->withTotals($cart->fresh(['items.product', 'items.variant']));
    }

    public function addItem(int $userId, int $productId, ?int $variantId, int $quantity): CustomerCart
    {
        return DB::transaction(function () use ($userId, $productId, $variantId, $quantity): CustomerCart {
            $product = Product::query()->find($productId);
            if ($product === null || $product->status !== 'active') throw new ProductNotPurchasableException('Product is not available for purchase.');
            $variant = null;
            if ($variantId !== null) {
                $variant = ProductVariant::query()->where('product_id', $productId)->whereKey($variantId)->first();
                if ($variant === null || $variant->status !== 'active') throw new ProductNotPurchasableException('Product variant is not available for purchase.');
            } elseif ($product->type === 'variable') {
                throw new ProductNotPurchasableException('A product variant is required.');
            }
            $cart = $this->get($userId);
            $item = CustomerCartItem::query()->where('cart_id', $cart->id)->where('product_id', $productId)->where('variant_id', $variantId)->first();
            $newQuantity = ($item?->quantity ?? 0) + $quantity;
            $this->assertAvailable($productId, $variantId, $newQuantity, $product->name);
            if ($item === null) $item = new CustomerCartItem(['cart_id' => $cart->id, 'product_id' => $productId, 'variant_id' => $variantId]);
            $item->quantity = $newQuantity;
            $item->save();
            return $this->touch($cart);
        });
    }

    public function updateItem(int $userId, int $productId, ?int $variantId, int $quantity): CustomerCart
    {
        $cart = $this->get($userId);
        $item = CustomerCartItem::query()->where('cart_id', $cart->id)->where('product_id', $productId)->where('variant_id', $variantId)->first();
        if ($item === null) throw new CartItemNotFoundException('Cart item not found.');
        $this->assertAvailable($productId, $variantId, $quantity, $item->product?->name ?? 'product');
        $item->update(['quantity' => $quantity]);
        return $this->touch($cart);
    }

    public function removeItem(int $userId, int $productId, ?int $variantId): CustomerCart
    {
        $cart = $this->get($userId);
        $deleted = CustomerCartItem::query()->where('cart_id', $cart->id)->where('product_id', $productId)->where('variant_id', $variantId)->delete();
        if ($deleted === 0) throw new CartItemNotFoundException('Cart item not found.');
        return $this->touch($cart);
    }

    public function clear(int $userId): CustomerCart
    {
        $cart = $this->get($userId);
        $cart->items()->delete();
        return $this->touch($cart);
    }

    private function assertAvailable(int $productId, ?int $variantId, int $quantity, string $name): void
    {
        $inventory = InventoryItem::query()->where('product_id', $productId)->where('variant_id', $variantId)->first();
        if ($inventory !== null && $inventory->on_hand - $inventory->reserved < $quantity) throw new CartItemOutOfStockException("Insufficient stock for [{$name}].");
    }

    private function touch(object $cart): CustomerCart
    {
        $cart->update(['last_activity_at' => now(), 'abandoned_at' => null, 'recovered_at' => now(), 'recovery_reminder_count' => 0]);
        return $this->withTotals($cart->fresh(['items.product', 'items.variant']));
    }

    private function withTotals(object $cart): CustomerCart
    {
        return $cart;
    }
}
