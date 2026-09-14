<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_customer_can_read_and_update_own_profile_through_layered_flow(): void
    {
        $customer = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'phone' => '01000000000',
        ]);
        $customer->roles()->attach(Role::query()->where('slug', 'customer')->firstOrFail());

        $this->actingAs($customer)
            ->getJson('/api/v1/customer/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.name', 'Old Name');

        $this->actingAs($customer)
            ->patchJson('/api/v1/customer/profile', [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'phone' => '01111111111',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.email', 'new@example.com');
    }

    public function test_guest_cannot_access_customer_profile(): void
    {
        $this->getJson('/api/v1/customer/profile')->assertUnauthorized();
        $this->patchJson('/api/v1/customer/profile', [])->assertUnauthorized();
    }

    public function test_authenticated_user_without_customer_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/v1/customer/profile')
            ->assertForbidden()
            ->assertJsonPath('message', 'Missing permission: customer.profile.view.');
    }

    public function test_customer_profile_validation_rejects_duplicate_identifiers(): void
    {
        $customer = User::factory()->create();
        $customer->roles()->attach(Role::query()->where('slug', 'customer')->firstOrFail());
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($customer)
            ->patchJson('/api/v1/customer/profile', [
                'name' => 'Valid Name',
                'email' => 'taken@example.com',
            ])
            ->assertUnprocessable();
    }
}
