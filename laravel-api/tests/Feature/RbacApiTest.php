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
        $this->assertDatabaseHas('audit_logs', ['action' => 'role.permissions_updated', 'target_id' => $role->id, 'actor_id' => $admin->id]);
    }

    public function test_system_roles_cannot_be_modified(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('slug', 'admin')->firstOrFail());
        $owner = Role::query()->where('slug', 'owner')->firstOrFail();

        $this->actingAs($admin)->patchJson('/api/v1/roles/'.$owner->id.'/permissions', ['permissions' => ['reports.view']])->assertForbidden();
    }

    public function test_custom_role_can_be_toggled_but_assigned_role_cannot_be_disabled(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('slug', 'admin')->firstOrFail());
        $role = Role::query()->create(['name' => 'تشغيل مؤقت', 'slug' => 'temporary_ops', 'is_system' => false, 'is_active' => true]);

        $this->actingAs($admin)->patchJson('/api/v1/roles/'.$role->id.'/status', ['is_active' => false])
            ->assertOk()->assertJsonPath('data.is_active', false);
        $this->assertDatabaseHas('audit_logs', ['action' => 'role.status_updated', 'target_id' => $role->id]);

        $staff = User::factory()->create();
        $staff->roles()->attach($role);
        $this->actingAs($admin)->patchJson('/api/v1/roles/'.$role->id.'/status', ['is_active' => false])
            ->assertForbidden();
    }

    public function test_system_role_status_cannot_be_changed(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('slug', 'admin')->firstOrFail());
        $owner = Role::query()->where('slug', 'owner')->firstOrFail();

        $this->actingAs($admin)->patchJson('/api/v1/roles/'.$owner->id.'/status', ['is_active' => false])->assertForbidden();
    }

    public function test_admin_can_read_role_audit_with_pagination(): void
    {
        $this->seed(RbacSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('slug', 'admin')->firstOrFail());
        $role = Role::query()->create(['name' => 'تقارير', 'slug' => 'reports_role', 'is_system' => false, 'is_active' => true]);

        $this->actingAs($admin)->patchJson('/api/v1/roles/'.$role->id.'/permissions', ['permissions' => ['reports.view']])->assertOk();
        $this->actingAs($admin)->getJson('/api/v1/roles/'.$role->id.'/audit?per_page=1&page=1&action=permissions_updated&actor_id='.$admin->id.'&from=2026-01-01&to=2099-12-31')
            ->assertOk()->assertJsonPath('data.meta.total', 1)->assertJsonCount(1, 'data.items')->assertJsonPath('data.items.0.action', 'role.permissions_updated');
    }
}
