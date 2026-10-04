<?php

namespace Tests\Feature;

use App\Modules\Auth\Infrastructure\Models\Role;
use App\Modules\Auth\Infrastructure\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RbacApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_read_and_update_a_custom_role_permissions(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('slug', 'admin')->firstOrFail());
        $role = Role::query()->create(['name' => 'تسويق', 'slug' => 'marketing', 'is_system' => false, 'is_active' => true]);

        $this->actingAs($admin)->getJson('/api/v1/roles')->assertOk()->assertJsonPath('data.roles.0.slug', 'admin');
        $this->actingAs($admin)->patchJson('/api/v1/roles/'.$role->id.'/permissions', ['permissions' => ['reports.view']])->assertOk()->assertJsonPath('data.slug', 'marketing');
        $this->assertTrue($role->fresh()->permissions()->where('slug', 'reports.view')->exists());
    }

    public function test_system_roles_cannot_be_modified(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('slug', 'admin')->firstOrFail());
        $owner = Role::query()->where('slug', 'owner')->firstOrFail();

        $this->actingAs($admin)->patchJson('/api/v1/roles/'.$owner->id.'/permissions', ['permissions' => ['reports.view']])->assertForbidden();
    }
}
