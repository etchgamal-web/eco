<?php

namespace Tests\Feature;

use App\Modules\Auth\Infrastructure\Models\Role;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Order\Domain\Contracts\ReturnRepositoryInterface;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Order\Infrastructure\Models\OrderReturn;
use App\Modules\Payment\Infrastructure\Models\Payment;
use App\Shared\Domain\Data\OutboxMessage;
use App\Shared\Domain\Contracts\OutboxRepositoryInterface;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReturnsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_request_return_and_admin_can_approve_it(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->user('customer');
        $admin = $this->user('admin');
        $product = Product::query()->create(['name' => 'Return Product', 'slug' => 'return-product', 'type' => 'simple', 'status' => 'active', 'price' => 500]);
        $order = CustomerOrder::query()->create(['user_id' => $customer->id, 'status' => 'delivered', 'total_amount' => 1000, 'currency' => 'EGP']);
        $item = $order->items()->create(['product_id' => $product->id, 'name' => $product->name, 'quantity' => 2, 'unit_price' => 500]);
        Payment::query()->create(['order_id' => $order->id, 'user_id' => $customer->id, 'method' => 'cash_on_delivery', 'provider_reference' => 'returns-multiple-payment', 'amount' => 1000, 'currency' => 'EGP', 'status' => 'paid', 'idempotency_key' => 'returns-multiple-payment']);
        $return = $this->actingAs($customer)->postJson('/api/v1/customer/orders/'.$order->id.'/returns', ['reason' => 'Damaged', 'items' => [['order_item_id' => $item->id, 'quantity' => 1]]])->assertCreated()->assertJsonPath('data.status', 'pending')->json('data.id');
        $this->actingAs($admin)->getJson('/api/v1/returns')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($admin)->patchJson('/api/v1/returns/'.$return.'/approve')->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertDatabaseHas('outbox_events', ['deduplication_key' => 'return:approved:'.$return, 'event_type' => 'order.return.approved']);
        $this->actingAs($customer)->postJson('/api/v1/customer/orders/'.$order->id.'/returns', ['reason' => 'Again', 'items' => [['order_item_id' => $item->id, 'quantity' => 1]]])->assertCreated();
        $this->actingAs($customer)->postJson('/api/v1/customer/orders/'.$order->id.'/returns', ['reason' => 'Too much', 'items' => [['order_item_id' => $item->id, 'quantity' => 1]]])
            ->assertStatus(409);
    }

    public function test_admin_can_read_return_details_but_customer_cannot(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->user('customer');
        $admin = $this->user('admin');
        $product = Product::query()->create(['name' => 'Return Detail Product', 'slug' => 'return-detail-product', 'type' => 'simple', 'status' => 'active', 'price' => 250]);
        $order = CustomerOrder::query()->create(['user_id' => $customer->id, 'status' => 'delivered', 'total_amount' => 250, 'currency' => 'EGP']);
        $item = $order->items()->create(['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'unit_price' => 250]);
        Payment::query()->create(['order_id' => $order->id, 'user_id' => $customer->id, 'method' => 'cash_on_delivery', 'provider_reference' => 'return-detail-payment', 'amount' => 250, 'currency' => 'EGP', 'status' => 'paid', 'idempotency_key' => 'return-detail-payment']);
        $return = $this->actingAs($customer)->postJson('/api/v1/customer/orders/'.$order->id.'/returns', ['reason' => 'Detail check', 'notes' => 'Handle carefully', 'items' => [['order_item_id' => $item->id, 'quantity' => 1]]])->assertCreated()->json('data.id');

        $this->actingAs($admin)->getJson('/api/v1/returns/'.$return)
            ->assertOk()
            ->assertJsonPath('data.id', $return)
            ->assertJsonPath('data.reason', 'Detail check')
            ->assertJsonCount(1, 'data.items');
        $this->actingAs($customer)->getJson('/api/v1/returns/'.$return)->assertForbidden();
    }

    public function test_admin_can_reject_return_during_inspection_with_notes(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->user('customer');
        $admin = $this->user('admin');
        $product = Product::query()->create(['name' => 'Inspection Product', 'slug' => 'inspection-product', 'type' => 'simple', 'status' => 'active', 'price' => 300]);
        $order = CustomerOrder::query()->create(['user_id' => $customer->id, 'status' => 'delivered', 'total_amount' => 300, 'currency' => 'EGP']);
        $item = $order->items()->create(['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'unit_price' => 300]);
        Payment::query()->create(['order_id' => $order->id, 'user_id' => $customer->id, 'method' => 'cash_on_delivery', 'provider_reference' => 'inspection-payment', 'amount' => 300, 'currency' => 'EGP', 'status' => 'paid', 'idempotency_key' => 'inspection-payment']);
        $return = $this->actingAs($customer)->postJson('/api/v1/customer/orders/'.$order->id.'/returns', ['reason' => 'Inspection check', 'items' => [['order_item_id' => $item->id, 'quantity' => 1]]])->assertCreated()->json('data.id');

        $this->actingAs($admin)->patchJson('/api/v1/returns/'.$return.'/approve')->assertOk();
        $this->actingAs($admin)->patchJson('/api/v1/returns/'.$return.'/receive')->assertOk();
        $this->actingAs($admin)->patchJson('/api/v1/returns/'.$return.'/inspect', ['accepted' => false, 'notes' => 'المنتج مستخدم'])->assertOk()->assertJsonPath('data.status', 'inspected_rejected')->assertJsonPath('data.inspection_notes', 'المنتج مستخدم');
    }

    public function test_only_delivered_orders_and_valid_quantities_can_be_returned(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->user('customer');
        $product = Product::query()->create(['name' => 'Pending Product', 'slug' => 'pending-product', 'type' => 'simple', 'status' => 'active', 'price' => 100]);
        $order = CustomerOrder::query()->create(['user_id' => $customer->id, 'status' => 'processing', 'total_amount' => 100, 'currency' => 'EGP']);
        $item = $order->items()->create(['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'unit_price' => 100]);
        $this->actingAs($customer)->postJson('/api/v1/customer/orders/'.$order->id.'/returns', ['reason' => 'Changed mind', 'items' => [['order_item_id' => $item->id, 'quantity' => 1]]])->assertStatus(409);
    }

    public function test_partial_returns_are_allocated_across_multiple_payments_without_exceeding_each_payment(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->user('customer');
        $product = Product::query()->create(['name' => 'Split Payment Product', 'slug' => 'split-payment-product', 'type' => 'simple', 'status' => 'active', 'price' => 500]);
        $order = CustomerOrder::query()->create(['user_id' => $customer->id, 'status' => 'delivered', 'total_amount' => 1000, 'currency' => 'EGP']);
        $item = $order->items()->create(['product_id' => $product->id, 'name' => $product->name, 'quantity' => 2, 'unit_price' => 500]);
        $firstPayment = Payment::query()->create(['order_id' => $order->id, 'user_id' => $customer->id, 'method' => 'cash_on_delivery', 'provider_reference' => 'split-payment-1', 'amount' => 500, 'currency' => 'EGP', 'status' => 'paid', 'idempotency_key' => 'split-payment-1']);
        $secondPayment = Payment::query()->create(['order_id' => $order->id, 'user_id' => $customer->id, 'method' => 'cash_on_delivery', 'provider_reference' => 'split-payment-2', 'amount' => 500, 'currency' => 'EGP', 'status' => 'paid', 'idempotency_key' => 'split-payment-2']);

        $firstReturn = $this->actingAs($customer)->postJson('/api/v1/customer/orders/'.$order->id.'/returns', ['reason' => 'First item', 'items' => [['order_item_id' => $item->id, 'quantity' => 1]]])->assertCreated()->json('data.id');
        $secondReturn = $this->actingAs($customer)->postJson('/api/v1/customer/orders/'.$order->id.'/returns', ['reason' => 'Second item', 'items' => [['order_item_id' => $item->id, 'quantity' => 1]]])->assertCreated()->json('data.id');

        $this->assertDatabaseHas('order_returns', ['id' => $firstReturn, 'payment_id' => $secondPayment->id, 'refund_amount' => 500]);
        $this->assertDatabaseHas('order_returns', ['id' => $secondReturn, 'payment_id' => $firstPayment->id, 'refund_amount' => 500]);
    }

    public function test_refund_completion_does_not_complete_a_return_before_inspection_acceptance(): void
    {
        $customer = User::factory()->create();
        $order = CustomerOrder::query()->create([
            'user_id' => $customer->id,
            'status' => 'delivered',
            'total_amount' => 1000,
            'subtotal_amount' => 1000,
            'currency' => 'EGP',
        ]);
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'method' => 'cash_on_delivery',
            'provider_reference' => 'return-state-test',
            'amount' => 1000,
            'currency' => 'EGP',
            'status' => 'paid',
            'idempotency_key' => 'return-state-payment',
        ]);
        $received = OrderReturn::query()->create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'user_id' => $customer->id,
            'status' => 'received',
            'reason' => 'Damaged',
            'refund_amount' => 300,
        ]);
        $accepted = OrderReturn::query()->create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'user_id' => $customer->id,
            'status' => 'inspected_accepted',
            'reason' => 'Wrong size',
            'refund_amount' => 200,
            'refund_requested_at' => now(),
        ]);

        app(ReturnRepositoryInterface::class)->completeRefundForPayment((int) $payment->id, 200);

        $this->assertDatabaseHas('order_returns', [
            'id' => $received->id,
            'status' => 'received',
            'completed_at' => null,
            'actual_customer_refund' => null,
        ]);
        $this->assertDatabaseHas('order_returns', [
            'id' => $accepted->id,
            'status' => 'completed',
            'actual_customer_refund' => 200,
        ]);
    }

    public function test_refund_completion_requires_a_requested_refund_and_matching_amount(): void
    {
        $customer = User::factory()->create();
        $order = CustomerOrder::query()->create([
            'user_id' => $customer->id,
            'status' => 'delivered',
            'total_amount' => 1000,
            'subtotal_amount' => 1000,
            'currency' => 'EGP',
        ]);
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'method' => 'cash_on_delivery',
            'provider_reference' => 'return-amount-test',
            'amount' => 1000,
            'currency' => 'EGP',
            'status' => 'paid',
            'idempotency_key' => 'return-amount-payment',
        ]);
        $notRequested = OrderReturn::query()->create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'user_id' => $customer->id,
            'status' => 'inspected_accepted',
            'reason' => 'Damaged',
            'refund_amount' => 200,
        ]);
        $differentAmount = OrderReturn::query()->create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'user_id' => $customer->id,
            'status' => 'inspected_accepted',
            'reason' => 'Wrong size',
            'refund_amount' => 250,
            'refund_requested_at' => now(),
        ]);

        app(ReturnRepositoryInterface::class)->completeRefundForPayment((int) $payment->id, 200);

        $this->assertDatabaseHas('order_returns', ['id' => $notRequested->id, 'status' => 'inspected_accepted', 'actual_customer_refund' => null]);
        $this->assertDatabaseHas('order_returns', ['id' => $differentAmount->id, 'status' => 'inspected_accepted', 'actual_customer_refund' => null]);
    }

    public function test_return_workflow_tracks_restock_refund_and_failure_independently(): void
    {
        $customer = User::factory()->create();
        $order = CustomerOrder::query()->create(['user_id' => $customer->id, 'status' => 'delivered', 'total_amount' => 100, 'currency' => 'EGP']);
        $return = OrderReturn::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'status' => 'inspected_accepted',
            'reason' => 'Workflow status test',
            'refund_amount' => 100,
        ]);
        $repository = app(ReturnRepositoryInterface::class);

        $repository->markWorkflowAttempt((int) $return->id);
        $repository->markRestocked((int) $return->id);
        $repository->markRefundRequested((int) $return->id);
        $repository->markWorkflowFailed((int) $return->id, 'provider timeout');

        $this->assertDatabaseHas('order_returns', [
            'id' => $return->id,
            'restock_status' => 'completed',
            'refund_status' => 'failed',
            'workflow_error' => 'provider timeout',
        ]);
    }

    public function test_restock_can_resume_after_timeout_without_double_processing(): void
    {
        $customer = User::factory()->create();
        $order = CustomerOrder::query()->create(['user_id' => $customer->id, 'status' => 'delivered', 'total_amount' => 100, 'currency' => 'EGP']);
        $return = OrderReturn::query()->create(['order_id' => $order->id, 'user_id' => $customer->id, 'status' => 'inspected_accepted', 'reason' => 'timeout', 'refund_amount' => 100]);
        $repository = app(ReturnRepositoryInterface::class);

        $repository->markWorkflowFailed((int) $return->id, 'restock timeout');
        $repository->markWorkflowAttempt((int) $return->id);
        $repository->markRestocked((int) $return->id);
        $repository->markRestocked((int) $return->id);

        $this->assertDatabaseHas('order_returns', ['id' => $return->id, 'restock_status' => 'completed', 'workflow_error' => null]);
    }

    public function test_duplicate_accepted_return_events_are_deduplicated(): void
    {
        $repository = app(OutboxRepositoryInterface::class);
        $message = new OutboxMessage('order.return.inspection.accepted', 'order_return', 91, ['return_id' => 91], deduplicationKey: 'return:inspection:91');

        $first = $repository->add($message);
        $second = $repository->add($message);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('outbox_events', 1);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());

        return $user;
    }
}
