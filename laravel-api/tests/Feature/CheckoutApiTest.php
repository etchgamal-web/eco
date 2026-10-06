<?php

namespace Tests\Feature;

use App\Modules\Auth\Infrastructure\Models\Role;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Customer\Infrastructure\Models\CustomerAddress;
use App\Modules\Customer\Infrastructure\Models\CustomerCart;
use App\Modules\Inventory\Infrastructure\Models\InventoryItem;
use App\Modules\Promotion\Infrastructure\Models\Coupon;
use App\Modules\Settings\Infrastructure\Models\Setting;
use App\Modules\Shipping\Infrastructure\Models\ShippingMethod;
use App\Modules\Tax\Infrastructure\Models\TaxRule;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_checkout_using_server_side_price_and_snapshot(): void
    {
        $this->seed(RbacSeeder::class);
        $user = $this->userWithRole('customer');
        $product = Product::query()->create([
            'name' => 'Checkout Product', 'slug' => 'checkout-product',
            'type' => 'simple', 'status' => 'active', 'price' => 1250,
        ]);
        $address = CustomerAddress::query()->create([
            'user_id' => $user->id, 'recipient_name' => 'Customer', 'phone' => '01000000000',
            'address_line1' => 'Street 1', 'city' => 'Cairo', 'country' => 'EG', 'is_default' => true,
        ]);
        $cart = CustomerCart::query()->create(['user_id' => $user->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 2]);
        InventoryItem::query()->create(['product_id' => $product->id, 'on_hand' => 5, 'reserved' => 0]);

        $response = $this->actingAs($user)->postJson('/api/v1/customer/checkout', [
            'address_id' => $address->id,
            'currency' => 'EGP',
            'idempotency_key' => 'checkout-test-1',
        ]);

        $response->assertCreated()->assertJsonPath('data.total_amount', 2500);
        $this->assertDatabaseHas('customer_order_items', [
            'name' => 'Checkout Product', 'quantity' => 2,
            'unit_price' => 1250, 'total_amount' => 2500,
        ]);
        $this->assertDatabaseHas('inventory_items', ['product_id' => $product->id, 'reserved' => 2]);
        $this->assertDatabaseCount('customer_cart_items', 0);
    }

    public function test_customer_can_preview_checkout_without_mutating_order_payment_inventory_or_cart(): void
    {
        $this->seed(RbacSeeder::class);
        $user = $this->userWithRole('customer');
        $product = Product::query()->create(['name' => 'Preview Product', 'slug' => 'preview-product', 'type' => 'simple', 'status' => 'active', 'price' => 1000]);
        $address = CustomerAddress::query()->create(['user_id' => $user->id, 'recipient_name' => 'Customer', 'phone' => '01000000000', 'address_line1' => 'Street 1', 'city' => 'Cairo', 'country' => 'EG', 'is_default' => true]);
        $cart = CustomerCart::query()->create(['user_id' => $user->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 2]);
        InventoryItem::query()->create(['product_id' => $product->id, 'on_hand' => 5, 'reserved' => 0]);
        TaxRule::query()->create(['name' => 'Egypt VAT', 'country' => 'EG', 'rate' => 14, 'is_active' => true]);
        $shipping = ShippingMethod::query()->create(['code' => 'preview-standard', 'name' => 'Preview Standard', 'base_fee' => 150, 'currency' => 'EGP', 'is_active' => true]);

        $this->actingAs($user)->postJson('/api/v1/customer/checkout/preview', [
            'address_id' => $address->id,
            'shipping_method_id' => $shipping->id,
            'currency' => 'EGP',
        ])->assertOk()
            ->assertJsonPath('data.subtotal_amount', 2000)
            ->assertJsonPath('data.discount_amount', 0)
            ->assertJsonPath('data.tax_amount', 280)
            ->assertJsonPath('data.shipping_amount', 150)
            ->assertJsonPath('data.total_amount', 2430);

        $this->assertDatabaseCount('customer_orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseHas('inventory_items', ['product_id' => $product->id, 'reserved' => 0]);
        $this->assertDatabaseCount('customer_cart_items', 1);
    }

    public function test_confirm_rejects_stale_price_and_returns_a_fresh_quote_without_mutations(): void
    {
        $fixture = $this->customerCheckoutScenario();
        $previewInput = $this->previewInput($fixture);
        $preview = $this->actingAs($fixture['user'])->postJson('/api/v1/customer/checkout/preview', $previewInput)
            ->assertOk()->json('data');

        $fixture['product']->update(['price' => 9000]);
        $response = $this->actingAs($fixture['user'])->postJson('/api/v1/customer/checkout', $this->confirmInput($previewInput, $preview, 'stale-price'));

        $response->assertStatus(409)
            ->assertJsonPath('error_code', 'checkout_preview_stale')
            ->assertJsonPath('data.items.0.unit_price', 9000)
            ->assertJsonPath('data.subtotal_amount', 18000)
            ->assertJsonPath('data.preview_token', fn ($token) => is_string($token) && strlen($token) === 64);
        $this->assertNoCheckoutMutations($fixture);
    }

    public function test_confirm_rejects_stock_depleted_after_preview_without_order_payment_or_reservation(): void
    {
        $fixture = $this->customerCheckoutScenario();
        $previewInput = $this->previewInput($fixture);
        $preview = $this->actingAs($fixture['user'])->postJson('/api/v1/customer/checkout/preview', $previewInput)
            ->assertOk()->json('data');

        $fixture['inventory']->update(['on_hand' => 1]);
        $this->actingAs($fixture['user'])->postJson('/api/v1/customer/checkout', $this->confirmInput($previewInput, $preview, 'stale-stock'))
            ->assertUnprocessable();

        $this->assertNoCheckoutMutations($fixture);
        $this->assertDatabaseHas('inventory_items', ['id' => $fixture['inventory']->id, 'on_hand' => 1, 'reserved' => 0]);
    }

    public function test_confirm_returns_updated_quote_if_shipping_fee_changes_after_preview(): void
    {
        $fixture = $this->customerCheckoutScenario();
        $previewInput = $this->previewInput($fixture);
        $preview = $this->actingAs($fixture['user'])->postJson('/api/v1/customer/checkout/preview', $previewInput)
            ->assertOk()->json('data');

        $fixture['shipping']->update(['base_fee' => 700]);
        $this->actingAs($fixture['user'])->postJson('/api/v1/customer/checkout', $this->confirmInput($previewInput, $preview, 'stale-shipping-fee'))
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'checkout_preview_stale')
            ->assertJsonPath('data.shipping_amount', 700)
            ->assertJsonPath('data.total_amount', 2700);

        $this->assertNoCheckoutMutations($fixture);
    }

    public function test_confirm_rejects_shipping_method_disabled_after_preview_without_mutations(): void
    {
        $fixture = $this->customerCheckoutScenario();
        $previewInput = $this->previewInput($fixture);
        $preview = $this->actingAs($fixture['user'])->postJson('/api/v1/customer/checkout/preview', $previewInput)
            ->assertOk()->json('data');

        $fixture['shipping']->update(['is_active' => false]);
        $this->actingAs($fixture['user'])->postJson('/api/v1/customer/checkout', $this->confirmInput($previewInput, $preview, 'disabled-shipping'))
            ->assertUnprocessable();

        $this->assertNoCheckoutMutations($fixture);
    }

    public function test_confirm_rejects_coupon_that_expires_after_preview_without_mutations(): void
    {
        $fixture = $this->customerCheckoutScenario();
        $coupon = Coupon::query()->create(['code' => 'STALEEXP', 'type' => 'percent', 'value' => 10, 'is_active' => true]);
        $previewInput = $this->previewInput($fixture, 'STALEEXP');
        $preview = $this->actingAs($fixture['user'])->postJson('/api/v1/customer/checkout/preview', $previewInput)
            ->assertOk()->json('data');

        $coupon->update(['ends_at' => now()->subSecond()]);
        $this->actingAs($fixture['user'])->postJson('/api/v1/customer/checkout', $this->confirmInput($previewInput, $preview, 'expired-coupon'))
            ->assertStatus(409);

        $this->assertNoCheckoutMutations($fixture);
        $this->assertDatabaseCount('coupon_usages', 0);
    }

    public function test_confirm_rejects_coupon_when_last_usage_is_consumed_after_preview(): void
    {
        $fixture = $this->customerCheckoutScenario();
        $coupon = Coupon::query()->create(['code' => 'STALELIMIT', 'type' => 'percent', 'value' => 10, 'usage_limit' => 1, 'is_active' => true]);
        $previewInput = $this->previewInput($fixture, 'STALELIMIT');
        $preview = $this->actingAs($fixture['user'])->postJson('/api/v1/customer/checkout/preview', $previewInput)
            ->assertOk()->json('data');

        $otherCustomer = User::factory()->create();
        $coupon->usages()->create(['user_id' => $otherCustomer->id, 'discount_amount' => 100]);
        $this->actingAs($fixture['user'])->postJson('/api/v1/customer/checkout', $this->confirmInput($previewInput, $preview, 'exhausted-coupon'))
            ->assertStatus(409);

        $this->assertNoCheckoutMutations($fixture);
        $this->assertDatabaseCount('coupon_usages', 1);
    }

    public function test_confirm_rejects_coupon_if_minimum_order_changes_after_preview(): void
    {
        $fixture = $this->customerCheckoutScenario();
        $coupon = Coupon::query()->create(['code' => 'STALEMIN', 'type' => 'percent', 'value' => 10, 'minimum_order_amount' => 1000, 'is_active' => true]);
        $previewInput = $this->previewInput($fixture, 'STALEMIN');
        $preview = $this->actingAs($fixture['user'])->postJson('/api/v1/customer/checkout/preview', $previewInput)
            ->assertOk()->json('data');

        $coupon->update(['minimum_order_amount' => 2500]);
        $this->actingAs($fixture['user'])->postJson('/api/v1/customer/checkout', $this->confirmInput($previewInput, $preview, 'coupon-minimum-changed'))
            ->assertStatus(409);

        $this->assertNoCheckoutMutations($fixture);
        $this->assertDatabaseCount('coupon_usages', 0);
    }

    public function test_guest_can_checkout_when_store_setting_allows_it(): void
    {
        Setting::query()->create([
            'group' => 'checkout', 'key' => 'checkout.require_authentication', 'value' => '0',
            'type' => 'boolean', 'is_secret' => false, 'is_encrypted' => false,
        ]);
        $product = Product::query()->create([
            'name' => 'Guest Product', 'slug' => 'guest-product',
            'type' => 'simple', 'status' => 'active', 'price' => 750,
        ]);
        InventoryItem::query()->create(['product_id' => $product->id, 'on_hand' => 3, 'reserved' => 0]);

        $response = $this->postJson('/api/v1/customer/checkout', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'guest' => [
                'name' => 'Guest Customer', 'email' => 'guest@example.com', 'phone' => '01000000000',
                'address_line1' => 'Street 1', 'city' => 'Cairo', 'country' => 'EG',
            ],
            'idempotency_key' => 'guest-checkout-1',
        ]);

        $response->assertCreated()->assertJsonPath('data.total_amount', 1500);
        $this->assertDatabaseHas('customer_orders', [
            'user_id' => null, 'guest_email' => 'guest@example.com', 'guest_phone' => '01000000000',
        ]);
        $this->assertDatabaseHas('inventory_items', ['product_id' => $product->id, 'reserved' => 2]);
    }

    public function test_guest_checkout_is_rejected_by_default(): void
    {
        $this->postJson('/api/v1/customer/checkout', [])->assertUnauthorized();
    }

    public function test_checkout_is_idempotent_for_the_same_key(): void
    {
        $this->seed(RbacSeeder::class);
        $user = $this->userWithRole('customer');
        $product = Product::query()->create([
            'name' => 'Idempotent Product', 'slug' => 'idempotent-product',
            'type' => 'simple', 'status' => 'active', 'price' => 100,
        ]);
        $address = CustomerAddress::query()->create([
            'user_id' => $user->id, 'recipient_name' => 'Customer', 'phone' => '01000000000',
            'address_line1' => 'Street 1', 'city' => 'Cairo', 'country' => 'EG', 'is_default' => true,
        ]);
        $cart = CustomerCart::query()->create(['user_id' => $user->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);
        InventoryItem::query()->create(['product_id' => $product->id, 'on_hand' => 1, 'reserved' => 0]);

        $payload = ['address_id' => $address->id];
        $headers = ['Idempotency-Key' => 'same-key'];
        $first = $this->actingAs($user)->postJson('/api/v1/customer/checkout', $payload, $headers);
        $second = $this->actingAs($user)->postJson('/api/v1/customer/checkout', $payload, $headers);

        $first->assertCreated();
        $second->assertCreated()->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertDatabaseCount('customer_orders', 1);
    }

    public function test_checkout_applies_coupon_and_tax_and_snapshots_both(): void
    {
        $this->seed(RbacSeeder::class);
        $user = $this->userWithRole('customer');
        $product = Product::query()->create(['name' => 'Taxed Product', 'slug' => 'taxed-product', 'type' => 'simple', 'status' => 'active', 'price' => 1000]);
        $address = CustomerAddress::query()->create(['user_id' => $user->id, 'recipient_name' => 'Customer', 'phone' => '01000000000', 'address_line1' => 'Street 1', 'city' => 'Cairo', 'country' => 'EG', 'is_default' => true]);
        $cart = CustomerCart::query()->create(['user_id' => $user->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);
        InventoryItem::query()->create(['product_id' => $product->id, 'on_hand' => 2, 'reserved' => 0]);
        Coupon::query()->create(['code' => 'SAVE10', 'type' => 'percent', 'value' => 10, 'is_active' => true]);
        TaxRule::query()->create(['name' => 'Egypt VAT', 'country' => 'EG', 'rate' => 14, 'is_active' => true]);

        $this->actingAs($user)->postJson('/api/v1/customer/checkout', ['address_id' => $address->id, 'coupon_code' => 'save10'])
            ->assertCreated()->assertJsonPath('data.subtotal_amount', 1000)->assertJsonPath('data.discount_amount', 100)
            ->assertJsonPath('data.tax_amount', 126)->assertJsonPath('data.total_amount', 1026)->assertJsonPath('data.coupon_code', 'SAVE10');
        $this->assertDatabaseCount('coupon_usages', 1);
    }

    public function test_checkout_creates_order_and_payment_without_creating_shipment(): void
    {
        $this->seed(RbacSeeder::class);
        $user = $this->userWithRole('customer');
        $product = Product::query()->create([
            'name' => 'Full Flow Product', 'slug' => 'full-flow-product',
            'type' => 'simple', 'status' => 'active', 'price' => 1250,
        ]);
        $address = CustomerAddress::query()->create([
            'user_id' => $user->id, 'recipient_name' => 'Customer', 'phone' => '01000000000',
            'address_line1' => 'Street 1', 'city' => 'Cairo', 'country' => 'EG', 'is_default' => true,
        ]);
        $cart = CustomerCart::query()->create(['user_id' => $user->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 2]);
        InventoryItem::query()->create(['product_id' => $product->id, 'on_hand' => 5, 'reserved' => 0]);
        $response = $this->actingAs($user)->postJson('/api/v1/customer/checkout', [
            'address_id' => $address->id,
            'currency' => 'EGP',
            'idempotency_key' => 'full-flow-order',
            'payment_method' => 'cash_on_delivery',
            'payment_idempotency_key' => 'full-flow-payment',
        ]);

        $response->assertCreated()->assertJsonPath('data.total_amount', 2500);
        $orderId = $response->json('data.id');
        $this->assertDatabaseHas('inventory_items', ['product_id' => $product->id, 'reserved' => 2]);
        $this->assertDatabaseMissing('shipments', ['order_id' => $orderId]);
        $this->assertDatabaseHas('payments', ['order_id' => $orderId, 'amount' => 2500, 'status' => 'pending']);
        $this->assertDatabaseCount('customer_orders', 1);
        $this->assertDatabaseCount('shipments', 0);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_checkout_includes_selected_shipping_method_in_server_total(): void
    {
        $this->seed(RbacSeeder::class);
        $user = $this->userWithRole('customer');
        $product = Product::query()->create(['name' => 'Shipping Product', 'slug' => 'shipping-product', 'type' => 'simple', 'status' => 'active', 'price' => 1000]);
        $address = CustomerAddress::query()->create(['user_id' => $user->id, 'recipient_name' => 'Customer', 'phone' => '01000000000', 'address_line1' => 'Street 1', 'city' => 'Cairo', 'country' => 'EG', 'is_default' => true]);
        $cart = CustomerCart::query()->create(['user_id' => $user->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);
        InventoryItem::query()->create(['product_id' => $product->id, 'on_hand' => 2, 'reserved' => 0]);
        $shipping = ShippingMethod::query()->create(['code' => 'standard', 'name' => 'Standard', 'base_fee' => 150, 'currency' => 'EGP', 'is_active' => true]);

        $this->actingAs($user)->postJson('/api/v1/customer/checkout', ['address_id' => $address->id, 'shipping_method_id' => $shipping->id, 'currency' => 'EGP', 'idempotency_key' => 'shipping-checkout-1'])
            ->assertCreated()->assertJsonPath('data.subtotal_amount', 1000)->assertJsonPath('data.shipping_amount', 150)->assertJsonPath('data.total_amount', 1150);
    }

    public function test_checkout_does_not_validate_or_create_shipping_provider_state(): void
    {
        $this->seed(RbacSeeder::class);
        $user = $this->userWithRole('customer');
        $product = Product::query()->create([
            'name' => 'Rollback Product', 'slug' => 'rollback-product',
            'type' => 'simple', 'status' => 'active', 'price' => 500,
        ]);
        $address = CustomerAddress::query()->create([
            'user_id' => $user->id, 'recipient_name' => 'Customer', 'phone' => '01000000000',
            'address_line1' => 'Street 1', 'city' => 'Cairo', 'country' => 'EG', 'is_default' => true,
        ]);
        $cart = CustomerCart::query()->create(['user_id' => $user->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 2]);
        InventoryItem::query()->create(['product_id' => $product->id, 'on_hand' => 5, 'reserved' => 0]);

        $this->actingAs($user)->postJson('/api/v1/customer/checkout', [
            'address_id' => $address->id,
            'currency' => 'EGP',
            'idempotency_key' => 'rollback-order',
        ])->assertCreated();

        $this->assertDatabaseCount('customer_orders', 1);
        $this->assertDatabaseCount('shipments', 0);
        $this->assertDatabaseHas('inventory_items', ['product_id' => $product->id, 'on_hand' => 5, 'reserved' => 2]);
        $this->assertDatabaseCount('customer_cart_items', 0);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());

        return $user;
    }

    /** @return array{user: User, product: Product, address: CustomerAddress, cart: CustomerCart, inventory: InventoryItem, shipping: ShippingMethod} */
    private function customerCheckoutScenario(): array
    {
        $this->seed(RbacSeeder::class);
        $user = $this->userWithRole('customer');
        $product = Product::query()->create(['name' => 'Stale Preview Product', 'slug' => 'stale-preview-product', 'type' => 'simple', 'status' => 'active', 'price' => 1000]);
        $address = CustomerAddress::query()->create(['user_id' => $user->id, 'recipient_name' => 'Customer', 'phone' => '01000000000', 'address_line1' => 'Street 1', 'city' => 'Cairo', 'country' => 'EG', 'is_default' => true]);
        $cart = CustomerCart::query()->create(['user_id' => $user->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 2]);
        $inventory = InventoryItem::query()->create(['product_id' => $product->id, 'on_hand' => 5, 'reserved' => 0]);
        $shipping = ShippingMethod::query()->create(['code' => 'stale-preview-standard', 'name' => 'Stale Preview Standard', 'base_fee' => 500, 'currency' => 'EGP', 'is_active' => true]);

        return compact('user', 'product', 'address', 'cart', 'inventory', 'shipping');
    }

    /** @param array<string, mixed> $fixture
     * @return array<string, mixed>
     */
    private function previewInput(array $fixture, ?string $couponCode = null): array
    {
        return [
            'address_id' => $fixture['address']->id,
            'shipping_method_id' => $fixture['shipping']->id,
            'currency' => 'EGP',
            'coupon_code' => $couponCode,
        ];
    }

    /** @param array<string, mixed> $previewInput
     * @param  array<string, mixed>  $preview
     * @return array<string, mixed>
     */
    private function confirmInput(array $previewInput, array $preview, string $key): array
    {
        return array_merge($previewInput, [
            'preview_token' => $preview['preview_token'],
            'payment_method' => 'cash_on_delivery',
            'idempotency_key' => 'checkout-'.$key,
            'payment_idempotency_key' => 'payment-'.$key,
        ]);
    }

    /** @param array<string, mixed> $fixture */
    private function assertNoCheckoutMutations(array $fixture): void
    {
        $this->assertDatabaseCount('customer_orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseHas('inventory_items', ['id' => $fixture['inventory']->id, 'reserved' => 0]);
        $this->assertDatabaseCount('customer_cart_items', 1);
    }
}
