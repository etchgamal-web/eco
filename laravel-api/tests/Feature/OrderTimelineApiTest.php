<?php

namespace Tests\Feature;

use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Auth\Infrastructure\Models\Role;
use App\Modules\Auth\Infrastructure\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrderTimelineApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_timeline_contains_creation_and_review_workflow_events(): void
    {
        $this->seed(RbacSeeder::class);
        $manager = $this->userWithRole('order_manager');
        $customer = User::factory()->create();
        $order = CustomerOrder::query()->create([
            'user_id' => $customer->id,
            'status' => 'pending',
            'total_amount' => 100,
            'currency' => 'EGP',
        ]);

        $this->actingAs($manager)->postJson("/api/v1/orders/{$order->id}/review")
            ->assertCreated();
        $this->actingAs($manager)->postJson("/api/v1/orders/{$order->id}/contact", [
            'contact_result' => 'confirmed',
            'notes' => 'Customer confirmed by phone.',
        ])->assertOk();
        $this->actingAs($manager)->postJson("/api/v1/orders/{$order->id}/confirm")
            ->assertOk();

        $this->actingAs($manager)->getJson("/api/v1/orders/{$order->id}/timeline")
            ->assertOk()
            ->assertJsonPath('data.0.event', 'order_created')
            ->assertJsonFragment(['event' => 'status_changed', 'from_status' => 'pending', 'to_status' => 'reviewing'])
            ->assertJsonFragment(['event' => 'review_started'])
            ->assertJsonFragment(['event' => 'customer_contacted'])
            ->assertJsonFragment(['event' => 'order_confirmed']);
    }

    public function test_timeline_returns_not_found_for_unknown_order(): void
    {
        $this->seed(RbacSeeder::class);
        $manager = $this->userWithRole('order_manager');

        $this->actingAs($manager)->getJson('/api/v1/orders/999999/timeline')->assertNotFound();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());

        return $user;
    }
}
