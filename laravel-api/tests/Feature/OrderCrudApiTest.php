<?php

namespace Tests\Feature;

use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Inventory\Infrastructure\Models\InventoryItem;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Auth\Infrastructure\Models\Role;
use App\Modules\Shipping\Infrastructure\Models\Shipment;
use App\Modules\Shipping\Infrastructure\Models\ShippingMethod;
use App\Modules\Auth\Infrastructure\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrderCrudApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_list_and_show_only_owned_orders(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $other = User::factory()->create();
        $owned = CustomerOrder::query()->create(['user_id' => $customer->id, 'status' => 'pending', 'total_amount' => 100, 'currency' => 'EGP']);
        $foreign = CustomerOrder::query()->create(['user_id' => $other->id, 'status' => 'pending', 'total_amount' => 200, 'currency' => 'EGP']);

        $this->actingAs($customer)->getJson('/api/v1/customer/orders')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($customer)->getJson("/api/v1/customer/orders/{$owned->id}")->assertOk()->assertJsonPath('data.id', $owned->id);
        $this->actingAs($customer)->getJson("/api/v1/customer/orders/{$foreign->id}")->assertNotFound();
    }

    public function test_order_manager_must_review_and_contact_before_confirmation(): void
    {
        $this->seed(RbacSeeder::class);
        $manager = $this->userWithRole('order_manager');
        $order = CustomerOrder::query()->create(['user_id' => User::factory()->create()->id, 'status' => 'pending', 'total_amount' => 100, 'currency' => 'EGP']);

        $this->actingAs($manager)->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'confirmed'])
            ->assertConflict();
        $this->actingAs($manager)->postJson("/api/v1/orders/{$order->id}/review")
            ->assertCreated()->assertJsonPath('data.order.status', 'reviewing');
        $this->actingAs($manager)->postJson("/api/v1/orders/{$order->id}/confirm")
            ->assertConflict();
        $this->actingAs($manager)->postJson("/api/v1/orders/{$order->id}/contact", [
            'contact_result' => 'confirmed', 'notes' => 'Customer confirmed the order.',
        ])->assertOk();
        $this->actingAs($manager)->postJson("/api/v1/orders/{$order->id}/confirm")
            ->assertOk()->assertJsonPath('data.order.status', 'confirmed');
    }

    public function test_customer_can_cancel_pending_order_but_cannot_cancel_delivered_order(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $pending = CustomerOrder::query()->create(['user_id' => $customer->id, 'status' => 'pending', 'total_amount' => 100, 'currency' => 'EGP']);
        $delivered = CustomerOrder::query()->create(['user_id' => $customer->id, 'status' => 'delivered', 'total_amount' => 100, 'currency' => 'EGP']);

        $this->actingAs($customer)->postJson("/api/v1/customer/orders/{$pending->id}/cancel")
            ->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->actingAs($customer)->postJson("/api/v1/customer/orders/{$delivered->id}/cancel")
            ->assertConflict();
    }

    public function test_manager_controls_customer_shipping_charge_and_order_total(): void
    {
        $this->seed(RbacSeeder::class);
        $manager = $this->userWithRole('order_manager');
        $order = CustomerOrder::query()->create([
            'user_id' => User::factory()->create()->id,
            'status' => 'processing',
            'subtotal_amount' => 1000,
            'discount_amount' => 100,
            'tax_amount' => 90,
            'shipping_amount' => 0,
            'shipping_cost' => 150,
            'total_amount' => 990,
            'currency' => 'EGP',
        ]);

        $this->actingAs($manager)->patchJson("/api/v1/orders/{$order->id}/shipping-charge", ['shipping_amount' => 50])
            ->assertOk()
            ->assertJsonPath('data.shipping_amount', 50)
            ->assertJsonPath('data.shipping_cost', 150)
            ->assertJsonPath('data.shipping_subsidy', 100)
            ->assertJsonPath('data.total_amount', 1040);
    }

    public function test_shipping_an_order_commits_reserved_inventory(): void
    {
        $this->seed(RbacSeeder::class);
        $manager = $this->userWithRole('order_manager');
        $customer = User::factory()->create();
        $product = Product::query()->create([
            'name' => 'Shipped Product', 'slug' => 'shipped-product',
            'type' => 'simple', 'status' => 'active', 'price' => 100,
        ]);
        $order = CustomerOrder::query()->create([
            'user_id' => $customer->id, 'status' => 'processing',
            'total_amount' => 200, 'currency' => 'EGP',
        ]);
        $order->items()->create([
            'product_id' => $product->id, 'name' => $product->name,
            'quantity' => 2, 'unit_price' => 100, 'total_amount' => 200,
        ]);
        $inventory = InventoryItem::query()->create([
            'product_id' => $product->id, 'on_hand' => 5, 'reserved' => 2,
        ]);
        $method = ShippingMethod::query()->create([
            'code' => 'test', 'name' => 'Test', 'base_fee' => 0, 'currency' => 'EGP', 'is_active' => true,
        ]);
        Shipment::query()->create([
            'order_id' => $order->id, 'user_id' => $customer->id, 'shipping_method_id' => $method->id,
            'method_code' => $method->code, 'provider_code' => 'bosta', 'fee' => 0, 'currency' => 'EGP', 'status' => 'picked_up', 'creation_status' => 'created',
            'address_snapshot' => [], 'idempotency_key' => 'test-shipment-'.$order->id,
        ]);

        $this->actingAs($manager)->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertOk()->assertJsonPath('data.status', 'shipped');

        $this->assertDatabaseHas('inventory_items', [
            'id' => $inventory->id, 'on_hand' => 3, 'reserved' => 0,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $inventory->id, 'quantity' => -2,
            'on_hand_after' => 3, 'reason' => 'sale',
        ]);
    }

    public function test_order_cannot_be_shipped_when_provider_creation_is_not_complete(): void
    {
        $this->seed(RbacSeeder::class);
        $manager = $this->userWithRole('order_manager');
        $customer = User::factory()->create();
        $order = CustomerOrder::query()->create([
            'user_id' => $customer->id, 'status' => 'processing',
            'total_amount' => 100, 'currency' => 'EGP',
        ]);
        $method = ShippingMethod::query()->create([
            'code' => 'test-uncreated', 'name' => 'Test Uncreated', 'base_fee' => 0,
            'currency' => 'EGP', 'is_active' => true,
        ]);
        Shipment::query()->create([
            'order_id' => $order->id, 'user_id' => $customer->id, 'shipping_method_id' => $method->id,
            'method_code' => $method->code, 'provider_code' => 'bosta', 'fee' => 0, 'currency' => 'EGP',
            'status' => 'picked_up', 'creation_status' => 'creation_pending',
            'address_snapshot' => [], 'idempotency_key' => 'uncreated-shipment-'.$order->id,
        ]);

        $this->actingAs($manager)->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertConflict();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());

        return $user;
    }
}
