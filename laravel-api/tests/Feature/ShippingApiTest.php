<?php

namespace Tests\Feature;

use App\Models\CustomerOrder;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Modules\Shipping\Domain\Contracts\ShippingProviderInterface;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ShippingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_list_methods_but_only_staff_can_create_shipment(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $manager = $this->userWithRole('order_manager');
        $method = ShippingMethod::query()->create(['code' => 'standard', 'name' => 'Standard', 'carrier' => 'bosta', 'base_fee' => 150, 'currency' => 'EGP', 'is_active' => true]);
        $order = CustomerOrder::query()->create(['user_id' => $customer->id, 'status' => 'processing', 'total_amount' => 1000, 'currency' => 'EGP', 'shipping_address' => ['city' => 'Cairo']]);

        $this->actingAs($customer)->getJson('/api/v1/customer/shipping-methods')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($customer)->postJson("/api/v1/orders/{$order->id}/shipments", ['shipping_method_id' => $method->id, 'idempotency_key' => 'shipment-1'])->assertForbidden();
        $shipment = $this->actingAs($manager)->postJson("/api/v1/orders/{$order->id}/shipments", ['shipping_method_id' => $method->id, 'provider_code' => 'bosta', 'idempotency_key' => 'shipment-1'])
            ->assertCreated()->assertJsonPath('data.fee', 150)->assertJsonPath('data.status', 'pending')->json('data');
        $this->assertDatabaseHas('customer_orders', ['id' => $order->id, 'shipping_cost' => 150]);
        $this->assertDatabaseHas('shipment_pricing_snapshots', ['shipment_id' => $shipment['id'], 'total_expected_cost' => 150]);
        $this->actingAs($customer)->getJson("/api/v1/customer/orders/{$order->id}/shipments")
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_manual_provider_accepts_tracking_without_external_api(): void
    {
        $shipment = new Shipment(['provider_code' => 'manual', 'metadata' => ['manual_tracking_number' => 'MAN-123']]);
        $provider = app(ShippingProviderInterface::class);
        self::assertTrue($provider->supports($shipment));
        self::assertSame('MAN-123', $provider->create($shipment)['tracking_number']);
    }

    public function test_staff_shipment_creation_is_idempotent_and_foreign_order_is_hidden(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $manager = $this->userWithRole('order_manager');
        $other = User::factory()->create();
        $method = ShippingMethod::query()->create(['code' => 'express', 'name' => 'Express', 'base_fee' => 300, 'currency' => 'EGP', 'is_active' => true]);
        $order = CustomerOrder::query()->create(['user_id' => $customer->id, 'status' => 'processing', 'total_amount' => 1000, 'currency' => 'EGP', 'shipping_address' => ['city' => 'Cairo']]);
        $foreign = CustomerOrder::query()->create(['user_id' => $other->id, 'status' => 'processing', 'total_amount' => 1000, 'currency' => 'EGP', 'shipping_address' => ['city' => 'Giza']]);
        $payload = ['shipping_method_id' => $method->id, 'provider_code' => 'bosta', 'idempotency_key' => 'same-shipment'];

        $first = $this->actingAs($manager)->postJson("/api/v1/orders/{$order->id}/shipments", $payload);
        $second = $this->actingAs($manager)->postJson("/api/v1/orders/{$order->id}/shipments", $payload);
        $first->assertCreated();
        $second->assertCreated()->assertJsonPath('data.id', $first->json('data.id'));
        $this->actingAs($customer)->getJson("/api/v1/customer/orders/{$foreign->id}/shipments")->assertNotFound();
    }

    public function test_owner_can_manage_methods_and_update_shipment_status(): void
    {
        $this->seed(RbacSeeder::class);
        $owner = $this->userWithRole('owner');
        $method = $this->actingAs($owner)->postJson('/api/v1/shipping-methods', ['code' => 'same-day', 'name' => 'Same Day', 'base_fee' => 500, 'currency' => 'EGP', 'is_active' => true])
            ->assertCreated()->json('data');
        $this->actingAs($owner)->patchJson('/api/v1/shipping-methods/'.$method['id'], ['name' => 'Same Day Updated'])
            ->assertOk()->assertJsonPath('data.name', 'Same Day Updated');
        $deletable = $this->actingAs($owner)->postJson('/api/v1/shipping-methods', ['code' => 'temporary', 'name' => 'Temporary', 'base_fee' => 50, 'currency' => 'EGP', 'is_active' => true])
            ->assertCreated()->json('data');

        $order = CustomerOrder::query()->create(['user_id' => $owner->id, 'status' => 'pending', 'total_amount' => 1000, 'currency' => 'EGP', 'shipping_address' => ['city' => 'Cairo']]);
        $shipment = Shipment::query()->create(['order_id' => $order->id, 'user_id' => $owner->id, 'shipping_method_id' => $method['id'], 'method_code' => 'same-day', 'provider_code' => 'same-day', 'fee' => 500, 'currency' => 'EGP', 'status' => 'provider_created', 'creation_status' => 'created', 'address_snapshot' => ['city' => 'Cairo'], 'idempotency_key' => 'admin-shipment']);
        $this->actingAs($owner)->patchJson("/api/v1/shipments/{$shipment->id}/status", ['status' => 'picked_up'])
            ->assertOk()->assertJsonPath('data.status', 'picked_up');
        $this->actingAs($owner)->deleteJson('/api/v1/shipping-methods/'.$deletable['id'])->assertNoContent();
    }

    public function test_delivered_shipment_completes_shipped_order(): void
    {
        $this->seed(RbacSeeder::class);
        $owner = $this->userWithRole('owner');
        $method = ShippingMethod::query()->create(['code' => 'delivery', 'name' => 'Delivery', 'base_fee' => 100, 'currency' => 'EGP', 'is_active' => true]);
        $order = CustomerOrder::query()->create(['user_id' => $owner->id, 'status' => 'shipped', 'total_amount' => 1000, 'currency' => 'EGP', 'shipping_address' => ['city' => 'Cairo']]);
        $shipment = Shipment::query()->create(['order_id' => $order->id, 'user_id' => $owner->id, 'shipping_method_id' => $method->id, 'method_code' => $method->code, 'fee' => 100, 'currency' => 'EGP', 'status' => 'out_for_delivery', 'address_snapshot' => ['city' => 'Cairo'], 'idempotency_key' => 'delivery-order']);

        $this->actingAs($owner)->patchJson("/api/v1/shipments/{$shipment->id}/status", ['status' => 'delivered'])
            ->assertOk()->assertJsonPath('data.status', 'delivered');
        $this->assertDatabaseHas('customer_orders', ['id' => $order->id, 'status' => 'delivered']);
    }

    public function test_customer_shipping_charge_cannot_exceed_carrier_cost(): void
    {
        $this->seed(RbacSeeder::class);
        $manager = $this->userWithRole('order_manager');
        $order = CustomerOrder::query()->create([
            'user_id' => $manager->id,
            'status' => 'processing',
            'total_amount' => 990,
            'subtotal_amount' => 1000,
            'discount_amount' => 100,
            'tax_amount' => 90,
            'shipping_amount' => 0,
            'shipping_cost' => 150,
            'shipping_subsidy' => 150,
            'currency' => 'EGP',
            'shipping_address' => ['city' => 'Cairo'],
        ]);

        $this->actingAs($manager)->patchJson("/api/v1/orders/{$order->id}/shipping-charge", ['shipping_amount' => 200])
            ->assertUnprocessable();
        $this->assertDatabaseHas('customer_orders', [
            'id' => $order->id,
            'shipping_amount' => 0,
            'shipping_cost' => 150,
            'shipping_subsidy' => 150,
            'total_amount' => 990,
        ]);

        $this->actingAs($manager)->patchJson("/api/v1/orders/{$order->id}/shipping-charge", ['shipping_amount' => 50])
            ->assertOk()
            ->assertJsonPath('data.shipping_amount', 50)
            ->assertJsonPath('data.shipping_subsidy', 100)
            ->assertJsonPath('data.total_amount', 1040);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());

        return $user;
    }
}
