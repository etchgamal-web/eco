<?php

namespace Tests\Feature;

use App\Modules\Auth\Infrastructure\Models\Role;
use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Order\Infrastructure\Models\CustomerOrderItem;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SalesAnalyticsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_read_sales_analytics_for_a_period(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = User::factory()->create(['email' => 'reports@example.com']);
        $admin->roles()->attach(Role::query()->where('slug', 'admin')->firstOrFail());
        $order = CustomerOrder::query()->create(['user_id' => $admin->id, 'status' => 'delivered', 'total_amount' => 1500, 'currency' => 'EGP', 'created_at' => '2026-10-04 10:00:00', 'updated_at' => '2026-10-04 10:00:00']);
        CustomerOrderItem::query()->create(['order_id' => $order->id, 'name' => 'منتج تجريبي', 'quantity' => 2, 'unit_price' => 750, 'total_amount' => 1500]);

        $response = $this->actingAs($admin)->getJson('/api/v1/reports/sales?from=2026-10-01&to=2026-10-05&compare=1');

        $response->assertOk()->assertJsonPath('data.current.summary.sales', 1500)->assertJsonPath('data.current.summary.orders', 1)->assertJsonPath('data.current.daily.0.date', '2026-10-04')->assertJsonPath('data.previous.from', '2026-09-26');
    }

    public function test_customer_cannot_read_sales_analytics(): void
    {
        $this->seed(RbacSeeder::class);
        $customer = User::factory()->create();
        $customer->roles()->attach(Role::query()->where('slug', 'customer')->firstOrFail());

        $this->actingAs($customer)->getJson('/api/v1/reports/sales')->assertForbidden();
    }
}
