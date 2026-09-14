<?php
namespace Tests\Feature;
use App\Models\CustomerOrder;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
final class ReturnsApiTest extends TestCase
{
    use RefreshDatabase;
    public function test_customer_can_request_return_and_admin_can_approve_it(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = $this->user('customer'); $admin = $this->user('admin');
        $product = Product::query()->create(['name' => 'Return Product', 'slug' => 'return-product', 'type' => 'simple', 'status' => 'active', 'price' => 500]);
        $order = CustomerOrder::query()->create(['user_id' => $customer->id, 'status' => 'delivered', 'total_amount' => 1000, 'currency' => 'EGP']);
        $item = $order->items()->create(['product_id' => $product->id, 'name' => $product->name, 'quantity' => 2, 'unit_price' => 500]);
        $return = $this->actingAs($customer)->postJson('/api/v1/customer/orders/'.$order->id.'/returns', ['reason' => 'Damaged', 'items' => [['order_item_id' => $item->id, 'quantity' => 1]]])->assertCreated()->assertJsonPath('data.status', 'pending')->json('data.id');
        $this->actingAs($admin)->getJson('/api/v1/returns')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($admin)->patchJson('/api/v1/returns/'.$return.'/approve')->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertDatabaseHas('outbox_events', ['deduplication_key' => 'return:approved:'.$return, 'event_type' => 'order.return.approved']);
        $this->actingAs($customer)->postJson('/api/v1/customer/orders/'.$order->id.'/returns', ['reason' => 'Again', 'items' => [['order_item_id' => $item->id, 'quantity' => 1]]])->assertStatus(409);
    }
    public function test_only_delivered_orders_and_valid_quantities_can_be_returned(): void
    {
        $this->seed(RbacSeeder::class); $customer = $this->user('customer');
        $product = Product::query()->create(['name' => 'Pending Product', 'slug' => 'pending-product', 'type' => 'simple', 'status' => 'active', 'price' => 100]);
        $order = CustomerOrder::query()->create(['user_id' => $customer->id, 'status' => 'processing', 'total_amount' => 100, 'currency' => 'EGP']);
        $item = $order->items()->create(['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'unit_price' => 100]);
        $this->actingAs($customer)->postJson('/api/v1/customer/orders/'.$order->id.'/returns', ['reason' => 'Changed mind', 'items' => [['order_item_id' => $item->id, 'quantity' => 1]]])->assertStatus(409);
    }
    private function user(string $role): User { $user = User::factory()->create(); $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail()); return $user; }
}
