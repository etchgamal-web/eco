<?php

namespace Tests\Feature;

use App\Models\CustomerOrder;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_and_list_cash_on_delivery_payment(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $order = $this->orderFor($customer, 1500);

        $this->actingAs($customer)->postJson("/api/v1/customer/orders/{$order->id}/payments", [
            'method' => 'cash_on_delivery', 'currency' => 'EGP', 'amount' => 1500, 'idempotency_key' => 'payment-1',
        ])->assertCreated()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.amount', 1500);

        $this->actingAs($customer)->getJson("/api/v1/customer/orders/{$order->id}/payments")
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_payment_uses_server_order_amount_and_is_idempotent(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $order = $this->orderFor($customer, 1500);
        $payload = ['method' => 'cash_on_delivery', 'currency' => 'EGP', 'idempotency_key' => 'same-payment'];

        $first = $this->actingAs($customer)->postJson("/api/v1/customer/orders/{$order->id}/payments", $payload);
        $second = $this->actingAs($customer)->postJson("/api/v1/customer/orders/{$order->id}/payments", $payload);

        $first->assertCreated();
        $second->assertCreated()->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_mismatched_payment_amount_is_rejected(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $order = $this->orderFor($customer, 1500);

        $this->actingAs($customer)->postJson("/api/v1/customer/orders/{$order->id}/payments", [
            'method' => 'cash_on_delivery', 'currency' => 'EGP', 'amount' => 1499, 'idempotency_key' => 'bad-payment',
        ])->assertUnprocessable();
    }

    public function test_owner_can_confirm_and_refund_payment_with_state_protection(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $owner = $this->userWithRole('owner');
        $order = $this->orderFor($customer, 1500);
        $payment = Payment::query()->create([
            'order_id' => $order->id, 'user_id' => $customer->id, 'method' => 'cash_on_delivery',
            'provider_reference' => 'cod-test', 'amount' => 1500, 'currency' => 'EGP',
            'status' => 'pending', 'idempotency_key' => 'owner-payment',
        ]);

        $this->actingAs($owner)->postJson("/api/v1/payments/{$payment->id}/confirm")
            ->assertOk()->assertJsonPath('data.status', 'paid');
        $this->assertDatabaseHas('customer_orders', ['id' => $order->id, 'status' => 'confirmed']);
        $this->actingAs($owner)->postJson("/api/v1/payments/{$payment->id}/refund")
            ->assertOk()->assertJsonPath('data.status', 'refunded');
        $this->assertDatabaseHas('customer_orders', ['id' => $order->id, 'status' => 'refunded']);
        $this->actingAs($owner)->postJson("/api/v1/payments/{$payment->id}/refund")
            ->assertConflict();
    }

    private function orderFor(User $user, int $amount): CustomerOrder
    {
        return CustomerOrder::query()->create([
            'user_id' => $user->id, 'status' => 'pending', 'total_amount' => $amount,
            'subtotal_amount' => $amount, 'currency' => 'EGP',
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());

        return $user;
    }
}
