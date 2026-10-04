<?php

namespace Tests\Feature;

use App\Modules\Auth\Infrastructure\Models\Role;
use App\Modules\Auth\Infrastructure\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class IntegrationEventsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_retry_failed_payment_webhook(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('slug', 'admin')->firstOrFail());
        $id = DB::table('payment_webhook_events')->insertGetId(['provider' => 'paymob', 'event_id' => 'evt-test-1', 'event_type' => 'payment.failed', 'status' => 'failed', 'payment_reference' => null, 'payload' => '{}', 'processing_error' => 'timeout', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($admin)->getJson('/api/v1/integrations/events?source=payment&status=failed')->assertOk()->assertJsonPath('data.0.event_id', 'evt-test-1');
        $this->actingAs($admin)->postJson('/api/v1/integrations/events/payment/'.$id.'/retry')->assertOk()->assertJsonPath('data.status', 'retrying');
        $this->assertDatabaseHas('payment_webhook_events', ['id' => $id, 'status' => 'retrying', 'processing_error' => null]);
    }

    public function test_admin_can_list_all_integration_sources_without_filters(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('slug', 'admin')->firstOrFail());

        $this->actingAs($admin)->getJson('/api/v1/integrations/events')->assertOk()->assertJsonStructure(['data']);
    }

    public function test_staff_without_integration_permission_is_forbidden(): void
    {
        $this->seed(RbacSeeder::class);
        $staff = User::factory()->create();
        $staff->roles()->attach(Role::query()->where('slug', 'support_agent')->firstOrFail());
        $this->actingAs($staff)->getJson('/api/v1/integrations/events')->assertForbidden();
    }
}
