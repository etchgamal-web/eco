<?php

namespace Tests\Feature;

use App\Models\CustomerOrder;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
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

    public function test_order_manager_can_update_status_and_invalid_transition_is_conflict(): void
    {
        $this->seed(RbacSeeder::class);
        $manager = $this->userWithRole('order_manager');
        $order = CustomerOrder::query()->create(['user_id' => User::factory()->create()->id, 'status' => 'pending', 'total_amount' => 100, 'currency' => 'EGP']);

        $this->actingAs($manager)->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'confirmed'])
            ->assertOk()->assertJsonPath('data.status', 'confirmed');
        $this->actingAs($manager)->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'delivered'])
            ->assertConflict();
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

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());
        return $user;
    }
}
