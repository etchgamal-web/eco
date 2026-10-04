<?php

namespace Tests\Feature;

use App\Modules\Auth\Infrastructure\Models\Role;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Order\Infrastructure\Models\OrderReturn;
use App\Modules\Payment\Application\UseCases\ReconcilePayment;
use App\Modules\Payment\Application\UseCases\RefundPayment;
use App\Modules\Payment\Domain\Exceptions\InvalidPaymentTransitionException;
use App\Modules\Payment\Infrastructure\Models\Payment;
use App\Modules\Payment\Infrastructure\Models\PaymentOperation;
use App\Shared\Infrastructure\Outbox\Models\OutboxEvent;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_global_payments_with_filters_and_pagination(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $owner = $this->userWithRole('owner');
        $order = $this->orderFor($customer, 1500);
        Payment::query()->create(['order_id' => $order->id, 'user_id' => $customer->id, 'method' => 'cash_on_delivery', 'provider_reference' => 'global-test', 'amount' => 1500, 'currency' => 'EGP', 'status' => 'pending', 'idempotency_key' => 'global-payment']);

        $this->actingAs($owner)->getJson('/api/v1/payments?status=pending&per_page=1')->assertOk()->assertJsonPath('data.0.provider_reference', 'global-test')->assertJsonPath('meta.total', 1);
        $this->actingAs($customer)->getJson('/api/v1/payments')->assertForbidden();
    }

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
        $this->assertDatabaseHas('customer_orders', ['id' => $order->id, 'status' => 'pending']);
        $this->actingAs($owner)->postJson("/api/v1/payments/{$payment->id}/refund")
            ->assertOk()->assertJsonPath('data.status', 'refunded');
        $this->assertDatabaseHas('customer_orders', ['id' => $order->id, 'status' => 'pending']);
        $this->actingAs($owner)->postJson("/api/v1/payments/{$payment->id}/refund")
            ->assertConflict();
    }

    public function test_partial_refunds_can_be_repeated_until_payment_amount_is_exhausted(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $owner = $this->userWithRole('owner');
        $order = $this->orderFor($customer, 1000);
        $payment = Payment::query()->create([
            'order_id' => $order->id, 'user_id' => $customer->id, 'method' => 'cash_on_delivery',
            'provider_reference' => 'partial-refund-test', 'amount' => 1000, 'currency' => 'EGP',
            'status' => 'paid', 'idempotency_key' => 'partial-refund-payment',
        ]);

        $this->actingAs($owner);
        $first = app(RefundPayment::class)->execute((int) $payment->id, 400);
        $this->assertSame('partially_refunded', $first->status);
        $second = app(RefundPayment::class)->execute((int) $payment->id, 600);
        $this->assertSame('refunded', $second->status);
        $this->expectException(InvalidPaymentTransitionException::class);
        app(RefundPayment::class)->execute((int) $payment->id, 1);
    }

    public function test_returns_with_the_same_amount_on_one_payment_get_distinct_refund_operations(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $order = $this->orderFor($customer, 1000);
        $payment = Payment::query()->create(['order_id' => $order->id, 'user_id' => $customer->id, 'method' => 'cash_on_delivery', 'provider_reference' => 'same-amount-payment', 'amount' => 1000, 'currency' => 'EGP', 'status' => 'paid', 'idempotency_key' => 'same-amount-payment']);
        $firstReturn = OrderReturn::query()->create(['order_id' => $order->id, 'payment_id' => $payment->id, 'user_id' => $customer->id, 'status' => 'inspected_accepted', 'reason' => 'first', 'refund_amount' => 200]);
        $secondReturn = OrderReturn::query()->create(['order_id' => $order->id, 'payment_id' => $payment->id, 'user_id' => $customer->id, 'status' => 'inspected_accepted', 'reason' => 'second', 'refund_amount' => 200]);

        app(RefundPayment::class)->execute((int) $payment->id, 200, (int) $firstReturn->id);
        app(RefundPayment::class)->execute((int) $payment->id, 200, (int) $secondReturn->id);

        $this->assertDatabaseCount('payment_operations', 2);
        $this->assertDatabaseHas('payment_operations', ['payment_id' => $payment->id, 'return_id' => $firstReturn->id, 'confirmed_amount' => 200]);
        $this->assertDatabaseHas('payment_operations', ['payment_id' => $payment->id, 'return_id' => $secondReturn->id, 'confirmed_amount' => 200]);
        $this->assertSame(2, PaymentOperation::query()->where('payment_id', $payment->id)->where('operation', 'refund')->where('status', 'confirmed')->count());
    }

    public function test_ambiguous_same_amount_refunds_reconcile_their_own_returns(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $order = $this->orderFor($customer, 1000);
        $payment = Payment::query()->create(['order_id' => $order->id, 'user_id' => $customer->id, 'method' => 'cash_on_delivery', 'provider_reference' => 'ambiguous-same-amount-payment', 'amount' => 1000, 'currency' => 'EGP', 'status' => 'paid', 'idempotency_key' => 'ambiguous-same-amount-payment']);
        $firstReturn = OrderReturn::query()->create(['order_id' => $order->id, 'payment_id' => $payment->id, 'user_id' => $customer->id, 'status' => 'inspected_accepted', 'reason' => 'first ambiguous', 'refund_amount' => 200, 'refund_requested_at' => now()]);
        $secondReturn = OrderReturn::query()->create(['order_id' => $order->id, 'payment_id' => $payment->id, 'user_id' => $customer->id, 'status' => 'inspected_accepted', 'reason' => 'second ambiguous', 'refund_amount' => 200, 'refund_requested_at' => now()]);
        $firstOperation = PaymentOperation::query()->create(['payment_id' => $payment->id, 'return_id' => $firstReturn->id, 'operation' => 'refund', 'status' => 'ambiguous', 'idempotency_key' => 'ambiguous-refund-first', 'requested_amount' => 200, 'attempt_count' => 1]);
        $secondOperation = PaymentOperation::query()->create(['payment_id' => $payment->id, 'return_id' => $secondReturn->id, 'operation' => 'refund', 'status' => 'ambiguous', 'idempotency_key' => 'ambiguous-refund-second', 'requested_amount' => 200, 'attempt_count' => 1]);

        app(ReconcilePayment::class)->execute((int) $payment->id, (int) $firstOperation->id);
        app(ReconcilePayment::class)->execute((int) $payment->id, (int) $secondOperation->id);

        $this->assertDatabaseHas('order_returns', ['id' => $firstReturn->id, 'status' => 'completed', 'actual_customer_refund' => 200]);
        $this->assertDatabaseHas('order_returns', ['id' => $secondReturn->id, 'status' => 'completed', 'actual_customer_refund' => 200]);
        $this->assertDatabaseHas('payment_operations', ['id' => $firstOperation->id, 'status' => 'confirmed', 'return_id' => $firstReturn->id]);
        $this->assertDatabaseHas('payment_operations', ['id' => $secondOperation->id, 'status' => 'confirmed', 'return_id' => $secondReturn->id]);
    }

    public function test_owner_can_view_operational_dashboard_and_retry_failed_outbox(): void
    {
        $this->seed(RbacSeeder::class);
        $owner = $this->userWithRole('owner');
        $event = OutboxEvent::query()->create([
            'aggregate_type' => 'payment', 'aggregate_id' => 77, 'event_type' => 'payment.create.requested',
            'deduplication_key' => 'dashboard-retry-event', 'status' => 'failed', 'attempt_count' => 5,
            'last_error' => 'provider timeout', 'payload' => [],
        ]);

        $this->actingAs($owner)->getJson('/api/v1/operations/dashboard')->assertOk()
            ->assertJsonStructure(['data' => ['generated_at', 'payments', 'ambiguous_payments', 'ambiguous_refunds', 'stuck_returns', 'failed_outbox', 'circuits']]);
        $this->actingAs($owner)->postJson('/api/v1/operations/outbox/'.$event->id.'/retry')->assertOk();
        $this->assertDatabaseHas('outbox_events', ['id' => $event->id, 'status' => 'pending', 'last_error' => null]);
    }

    public function test_manual_reconcile_requires_a_specific_operation_id(): void
    {
        $this->seed(RbacSeeder::class);
        $owner = $this->userWithRole('owner');
        $customer = $this->userWithRole('customer');
        $order = $this->orderFor($customer, 100);
        $payment = Payment::query()->create([
            'order_id' => $order->id, 'user_id' => $customer->id, 'method' => 'cash_on_delivery',
            'provider_reference' => 'manual-reconcile-test', 'amount' => 100, 'currency' => 'EGP',
            'status' => 'processing', 'idempotency_key' => 'manual-reconcile-payment',
        ]);
        PaymentOperation::query()->create([
            'payment_id' => $payment->id, 'operation' => 'confirm', 'status' => 'ambiguous',
            'idempotency_key' => 'manual-reconcile-operation', 'attempt_count' => 1,
        ]);

        $this->actingAs($owner)->postJson('/api/v1/operations/payments/'.$payment->id.'/reconcile')->assertUnprocessable();
    }

    public function test_refund_timeout_is_reconciled_using_the_specific_operation(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $order = $this->orderFor($customer, 1000);
        $payment = Payment::query()->create(['order_id' => $order->id, 'user_id' => $customer->id, 'method' => 'cash_on_delivery', 'amount' => 1000, 'currency' => 'EGP', 'status' => 'paid', 'idempotency_key' => 'refund-timeout-payment']);
        $operation = PaymentOperation::query()->create(['payment_id' => $payment->id, 'operation' => 'refund', 'status' => 'ambiguous', 'idempotency_key' => 'refund-timeout-operation', 'requested_amount' => 300, 'attempt_count' => 1, 'last_error' => 'provider timeout']);

        $result = app(ReconcilePayment::class)->execute((int) $payment->id, (int) $operation->id);

        $this->assertSame('partially_refunded', $result->status);
        $this->assertDatabaseHas('payment_operations', ['id' => $operation->id, 'status' => 'confirmed', 'confirmed_amount' => 300]);
    }

    public function test_failed_refund_can_be_retried_with_the_same_idempotency_key(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $order = $this->orderFor($customer, 1000);
        $payment = Payment::query()->create(['order_id' => $order->id, 'user_id' => $customer->id, 'method' => 'cash_on_delivery', 'provider_reference' => 'retry-refund', 'amount' => 1000, 'currency' => 'EGP', 'status' => 'paid', 'idempotency_key' => 'retry-refund-payment']);
        PaymentOperation::query()->create(['payment_id' => $payment->id, 'operation' => 'refund', 'status' => 'failed', 'idempotency_key' => 'refund:'.$payment->id.':250:retry-refund', 'requested_amount' => 250, 'attempt_count' => 1, 'last_error' => 'provider rejected']);

        $result = app(RefundPayment::class)->execute((int) $payment->id, 250);

        $this->assertSame('partially_refunded', $result->status);
        $this->assertDatabaseHas('payment_operations', ['payment_id' => $payment->id, 'operation' => 'refund', 'status' => 'confirmed', 'confirmed_amount' => 250]);
    }

    public function test_payment_failed_then_a_later_paid_payment_is_kept_as_a_separate_record(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $order = $this->orderFor($customer, 1000);
        $failed = Payment::query()->create(['order_id' => $order->id, 'user_id' => $customer->id, 'method' => 'cash_on_delivery', 'amount' => 1000, 'currency' => 'EGP', 'status' => 'failed', 'idempotency_key' => 'failed-payment']);
        $paid = Payment::query()->create(['order_id' => $order->id, 'user_id' => $customer->id, 'method' => 'cash_on_delivery', 'amount' => 1000, 'currency' => 'EGP', 'status' => 'paid', 'idempotency_key' => 'paid-retry-payment']);

        $this->assertDatabaseHas('payments', ['id' => $failed->id, 'status' => 'failed']);
        $this->assertDatabaseHas('payments', ['id' => $paid->id, 'status' => 'paid']);
        $this->assertNotSame($failed->id, $paid->id);
    }

    public function test_confirm_and_refund_ambiguous_operations_can_coexist(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->userWithRole('customer');
        $order = $this->orderFor($customer, 1000);
        $payment = Payment::query()->create(['order_id' => $order->id, 'user_id' => $customer->id, 'method' => 'cash_on_delivery', 'amount' => 1000, 'currency' => 'EGP', 'status' => 'ambiguous', 'idempotency_key' => 'both-ambiguous-payment']);
        PaymentOperation::query()->create(['payment_id' => $payment->id, 'operation' => 'confirm', 'status' => 'ambiguous', 'idempotency_key' => 'both-ambiguous-confirm', 'attempt_count' => 1]);
        PaymentOperation::query()->create(['payment_id' => $payment->id, 'operation' => 'refund', 'status' => 'ambiguous', 'idempotency_key' => 'both-ambiguous-refund', 'requested_amount' => 1000, 'attempt_count' => 1]);

        $this->assertSame(2, PaymentOperation::query()->where('payment_id', $payment->id)->where('status', 'ambiguous')->count());
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
