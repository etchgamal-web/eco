<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_user_can_register_and_is_logged_in(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()->assertJsonPath('data.email', 'customer@example.com');
        $this->assertAuthenticated('web');
        $this->assertDatabaseHas('users', ['email' => 'customer@example.com', 'status' => 'active']);
        $this->assertDatabaseHas('roles', ['slug' => 'customer']);
    }

    public function test_registration_requires_one_identifier_and_unique_identifier(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Customer',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'phone']);

        User::factory()->create(['email' => 'used@example.com']);
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Customer', 'email' => 'used@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_login_me_logout_and_change_password(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com', 'password' => 'password123']);

        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'customer@example.com', 'password' => 'password123',
        ])->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.id', $user->id);
        $this->postJson('/api/v1/auth/password', [
            'current_password' => 'password123', 'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertOk();
        $this->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertGuest('web');
    }

    public function test_invalid_or_inactive_login_is_rejected_and_protected_routes_require_authentication(): void
    {
        $user = User::factory()->create(['email' => 'inactive@example.com', 'status' => 'inactive']);
        $this->postJson('/api/v1/auth/login', [
            'identifier' => $user->email, 'password' => 'password',
        ])->assertStatus(401);
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        foreach (range(1, 5) as $_) {
            $this->postJson('/api/v1/auth/login', [
                'identifier' => 'missing@example.com', 'password' => 'wrong-password',
            ])->assertStatus(401);
        }

        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'missing@example.com', 'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_requests_receive_and_propagate_a_correlation_id(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertHeader('X-Correlation-ID');

        $this->withHeader('X-Correlation-ID', 'request-123')
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertHeader('X-Correlation-ID', 'request-123');
    }

    public function test_registration_attempts_are_rate_limited(): void
    {
        foreach (range(1, 3) as $_) {
            $this->postJson('/api/v1/auth/register', [])->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/register', [])->assertStatus(429);
    }

    public function test_role_permissions_and_direct_denials_are_enforced(): void
    {
        $admin = $this->createAdminUser();
        self::assertTrue($admin->hasPermission('products.create'));

        $user = User::factory()->create();
        self::assertFalse($user->hasPermission('products.create'));
        $user->permissionOverrides()->attach(
            \App\Models\Permission::query()->where('slug', 'products.create')->value('id'),
            ['allowed' => false],
        );
        self::assertFalse($user->hasPermission('products.create'));
    }
}
