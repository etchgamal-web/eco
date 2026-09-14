<?php

namespace Tests\Feature;

use App\Models\CustomerCart;
use App\Models\CustomerNotification;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CartLifecycleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_add_variant_and_totals_use_variant_price(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $product = $this->product(['type' => 'variable', 'price' => 100]);
        $variant = ProductVariant::query()->create(['product_id' => $product->id, 'sku' => 'VAR-1', 'price' => 250, 'status' => 'active', 'combination_hash' => 'test-var-1']);
        InventoryItem::query()->create(['product_id' => $product->id, 'variant_id' => $variant->id, 'on_hand' => 5, 'reserved' => 0]);

        $this->actingAs($customer)->postJson('/api/v1/customer/cart/items', ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 2])
            ->assertCreated()->assertJsonPath('data.totals.subtotal', 500);
    }

    public function test_cart_rejects_quantity_above_available_stock_and_can_be_cleared(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $product = $this->product();
        InventoryItem::query()->create(['product_id' => $product->id, 'variant_id' => null, 'on_hand' => 1, 'reserved' => 0]);

        $this->actingAs($customer)->postJson('/api/v1/customer/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertUnprocessable();
        $this->actingAs($customer)->postJson('/api/v1/customer/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertCreated();
        $this->actingAs($customer)->deleteJson('/api/v1/customer/cart')->assertOk()->assertJsonCount(0, 'data.items');
    }

    public function test_abandoned_cart_is_marked_and_notified_only_once(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $product = $this->product();
        $this->actingAs($customer)->postJson('/api/v1/customer/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertCreated();
        CustomerCart::query()->where('user_id', $customer->id)->update(['last_activity_at' => now()->subHours(48)]);

        $this->artisan('cart:mark-abandoned')->assertExitCode(0);
        $this->assertDatabaseHas('customer_carts', ['user_id' => $customer->id]);
        $this->assertDatabaseCount('customer_notifications', 1);
        $this->artisan('cart:mark-abandoned')->assertExitCode(0);
        $this->assertDatabaseCount('customer_notifications', 1);
    }

    private function product(array $overrides = []): Product
    {
        return Product::query()->create(array_merge(['name' => 'Cart Product', 'slug' => 'cart-product-' . uniqid(), 'type' => 'simple', 'status' => 'active', 'price' => 100], $overrides));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());
        return $user;
    }
}
