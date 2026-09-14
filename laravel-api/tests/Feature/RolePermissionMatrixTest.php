<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Modules\Auth\Domain\Contracts\AuthorizationServiceInterface;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RolePermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_customer_can_access_profile_but_cannot_create_products_or_update_settings(): void
    {
        $customer = $this->userWithRole('customer');

        $this->actingAs($customer)
            ->getJson('/api/v1/customer/profile')
            ->assertOk();

        $this->actingAs($customer)
            ->postJson('/api/v1/products', [])
            ->assertForbidden();

        $this->actingAs($customer)
            ->putJson('/api/v1/settings/store.name', ['value' => 'Blocked'])
            ->assertForbidden();

        $this->assertPermission($customer, 'customer.profile.view', true);
        $this->assertPermission($customer, 'products.create', false);
        $this->assertPermission($customer, 'settings.update', false);
    }

    public function test_product_manager_can_create_products_but_cannot_manage_orders(): void
    {
        $manager = $this->userWithRole('product_manager');

        $this->actingAs($manager)
            ->postJson('/api/v1/products', [])
            ->assertStatus(422);

        $this->assertPermission($manager, 'products.create', true);
        $this->assertPermission($manager, 'orders.manage', false);
    }

    public function test_order_manager_can_manage_orders_but_cannot_delete_products(): void
    {
        $manager = $this->userWithRole('order_manager');
        $product = Product::query()->create([
            'name' => 'Protected product',
            'slug' => 'protected-product',
            'type' => 'simple',
            'status' => 'active',
        ]);

        $this->actingAs($manager)
            ->deleteJson("/api/v1/products/{$product->id}")
            ->assertForbidden();

        $this->assertPermission($manager, 'orders.manage', true);
        $this->assertPermission($manager, 'products.delete', false);
    }

    public function test_owner_has_the_required_cross_domain_permissions(): void
    {
        $owner = $this->userWithRole('owner');

        foreach ([
            'customer.profile.view',
            'products.create',
            'products.delete',
            'orders.manage',
            'settings.update',
        ] as $permission) {
            $this->assertPermission($owner, $permission, true);
        }
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());

        return $user;
    }

    private function assertPermission(User $user, string $permission, bool $allowed): void
    {
        self::assertSame(
            $allowed,
            app(AuthorizationServiceInterface::class)->allows($user, $permission),
            "Unexpected permission result for {$user->roles()->first()->slug}: {$permission}."
        );
    }
}
