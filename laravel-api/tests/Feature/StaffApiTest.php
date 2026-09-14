<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class StaffApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_guest_cannot_access_staff(): void
    {
        $this->getJson('/api/v1/staff')->assertUnauthorized();
        $this->postJson('/api/v1/staff', [])->assertUnauthorized();
    }

    public function test_user_without_staff_permission_is_forbidden(): void
    {
        $user = $this->userWithRole('product_manager');
        $this->actingAs($user)->getJson('/api/v1/staff')->assertForbidden();
        $this->actingAs($user)->postJson('/api/v1/staff', [])->assertForbidden();
        $this->actingAs($user)->patchJson('/api/v1/staff/1', [])->assertForbidden();
        $this->actingAs($user)->deleteJson('/api/v1/staff/1')->assertForbidden();
    }

    public function test_owner_can_create_list_update_and_delete_staff(): void
    {
        $owner = $this->userWithRole('owner');
        $payload = ['name' => 'Catalog Staff', 'email' => 'staff@example.com', 'password' => 'password123', 'password_confirmation' => 'password123', 'roles' => ['product_manager']];
        $response = $this->actingAs($owner)->postJson('/api/v1/staff', $payload)->assertCreated()->assertJsonPath('data.email', 'staff@example.com')->assertJsonPath('data.roles.0.slug', 'product_manager');
        $id = $response->json('data.id');
        $this->actingAs($owner)->getJson('/api/v1/staff')->assertOk()->assertJsonFragment(['email' => 'staff@example.com']);
        $this->actingAs($owner)->patchJson("/api/v1/staff/{$id}", ['name' => 'Updated Staff', 'email' => 'staff@example.com', 'roles' => ['support_agent']])->assertOk()->assertJsonPath('data.name', 'Updated Staff')->assertJsonPath('data.roles.0.slug', 'support_agent');
        $this->actingAs($owner)->deleteJson("/api/v1/staff/{$id}")->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $id]);
    }

    public function test_patch_without_roles_preserves_existing_roles(): void
    {
        $owner = $this->userWithRole('owner');
        $staff = $this->userWithRole('product_manager');
        $this->actingAs($owner)->patchJson("/api/v1/staff/{$staff->id}", ['name' => 'Renamed Staff', 'email' => $staff->email])->assertOk()->assertJsonPath('data.roles.0.slug', 'product_manager');
    }

    public function test_owner_cannot_be_modified_or_deleted(): void
    {
        $actor = $this->userWithRole('owner');
        $protectedOwner = $this->userWithRole('owner');
        $this->actingAs($actor)->patchJson("/api/v1/staff/{$protectedOwner->id}", ['name' => 'Hacked', 'email' => $protectedOwner->email])->assertForbidden();
        $this->actingAs($actor)->deleteJson("/api/v1/staff/{$protectedOwner->id}")->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $protectedOwner->id, 'name' => $protectedOwner->name]);
    }

    public function test_staff_cannot_delete_or_deactivate_themselves(): void
    {
        $staff = $this->userWithRole('manager');
        $this->actingAs($staff)->deleteJson("/api/v1/staff/{$staff->id}")->assertForbidden();
        $this->actingAs($staff)->patchJson("/api/v1/staff/{$staff->id}", ['name' => $staff->name, 'email' => $staff->email, 'status' => 'inactive'])->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'status' => 'active']);
    }

    public function test_inactive_roles_cannot_be_assigned(): void
    {
        $owner = $this->userWithRole('owner');
        Role::query()->where('slug', 'support_agent')->update(['is_active' => false]);
        $payload = ['name' => 'Inactive Role Staff', 'email' => 'inactive-role@example.com', 'password' => 'password123', 'password_confirmation' => 'password123', 'roles' => ['support_agent']];
        $this->actingAs($owner)->postJson('/api/v1/staff', $payload)->assertUnprocessable()->assertJsonValidationErrors(['roles.0']);
    }

    public function test_last_owner_cannot_be_deactivated_or_deleted(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingAs($owner)->patchJson("/api/v1/staff/{$owner->id}", ['name' => $owner->name, 'email' => $owner->email, 'status' => 'inactive'])->assertForbidden();
        $this->actingAs($owner)->deleteJson("/api/v1/staff/{$owner->id}")->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $owner->id, 'status' => 'active']);
    }

    public function test_system_has_an_active_owner_after_staff_actions(): void
    {
        $owner = $this->userWithRole('owner');
        $secondOwner = $this->userWithRole('owner');
        $this->actingAs($owner)->deleteJson("/api/v1/staff/{$secondOwner->id}")->assertForbidden();
        $this->assertSame(2, User::query()->where('status', 'active')->whereHas('roles', fn ($query) => $query->where('slug', 'owner'))->count());
    }

    public function test_patch_validates_password_email_and_phone(): void
    {
        $owner = $this->userWithRole('owner');
        $existing = User::factory()->create(['email' => 'taken@example.com', 'phone' => '0100000000']);
        $staff = $this->userWithRole('manager');
        $this->actingAs($owner)->postJson('/api/v1/staff', ['name' => 'Invalid', 'email' => 'not-an-email', 'password' => 'short', 'password_confirmation' => 'different'])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->actingAs($owner)->patchJson("/api/v1/staff/{$staff->id}", ['name' => 'Duplicate', 'email' => $existing->email, 'phone' => $existing->phone])->assertUnprocessable()->assertJsonValidationErrors(['email', 'phone']);
    }

    public function test_password_is_hashed_and_email_and_phone_are_saved(): void
    {
        $owner = $this->userWithRole('owner');
        $response = $this->actingAs($owner)->postJson('/api/v1/staff', ['name' => 'Secure Staff', 'email' => 'secure@example.com', 'phone' => '0111111111', 'password' => 'password123', 'password_confirmation' => 'password123', 'roles' => ['manager']])->assertCreated();
        $staff = User::query()->findOrFail($response->json('data.id'));
        self::assertSame('secure@example.com', $staff->email);
        self::assertSame('0111111111', $staff->phone);
        self::assertTrue(Hash::check('password123', $staff->getRawOriginal('password')));
    }

    public function test_staff_request_validates_customer_role(): void
    {
        $owner = $this->userWithRole('owner');
        $payload = ['name' => 'Invalid', 'email' => 'invalid-role@example.com', 'password' => 'password123', 'password_confirmation' => 'password123', 'roles' => ['customer']];
        $this->actingAs($owner)->postJson('/api/v1/staff', $payload)->assertUnprocessable()->assertJsonValidationErrors(['roles.0']);
    }

    public function test_staff_lookup_of_customer_returns_not_found(): void
    {
        $owner = $this->userWithRole('owner');
        $customer = $this->userWithRole('customer');
        $this->actingAs($owner)->patchJson("/api/v1/staff/{$customer->id}", ['name' => 'No', 'email' => 'no@example.com'])->assertNotFound();
    }

    public function test_inactive_staff_cannot_authenticate(): void
    {
        $staff = User::factory()->create(['email' => 'inactive-staff@example.com', 'status' => 'inactive']);
        $this->postJson('/api/v1/auth/login', ['identifier' => $staff->email, 'password' => 'password'])->assertUnauthorized();
    }

    public function test_deleted_staff_cannot_authenticate(): void
    {
        $staff = User::factory()->create(['email' => 'deleted-staff@example.com']);
        $staff->delete();
        $this->postJson('/api/v1/auth/login', ['identifier' => 'deleted-staff@example.com', 'password' => 'password'])->assertUnauthorized();
    }

    public function test_explicit_empty_roles_removes_all_non_owner_roles(): void
    {
        $owner = $this->userWithRole('owner');
        $staff = $this->userWithRole('manager');
        $this->actingAs($owner)->patchJson("/api/v1/staff/{$staff->id}", ['name' => $staff->name, 'email' => $staff->email, 'roles' => []])->assertOk()->assertJsonPath('data.roles', []);
        $this->assertCount(0, $staff->fresh()->roles);
    }

    public function test_staff_password_can_be_updated_and_old_password_stops_working(): void
    {
        $owner = $this->userWithRole('owner');
        $staff = $this->userWithRole('manager');
        $this->actingAs($owner)->patchJson("/api/v1/staff/{$staff->id}", ['name' => $staff->name, 'email' => $staff->email, 'password' => 'new-password123', 'password_confirmation' => 'new-password123'])->assertOk();
        $this->postJson('/api/v1/auth/logout')->assertOk();
        $this->postJson('/api/v1/auth/login', ['identifier' => $staff->email, 'password' => 'password'])->assertUnauthorized();
        $this->postJson('/api/v1/auth/login', ['identifier' => $staff->email, 'password' => 'new-password123'])->assertOk();
    }

    public function test_staff_mutations_are_audited(): void
    {
        $owner = $this->userWithRole('owner');
        $response = $this->actingAs($owner)->postJson('/api/v1/staff', ['name' => 'Audited Staff', 'email' => 'audited@example.com', 'password' => 'password123', 'password_confirmation' => 'password123', 'roles' => ['manager']])->assertCreated();
        $id = $response->json('data.id');
        $this->actingAs($owner)->patchJson("/api/v1/staff/{$id}", ['name' => 'Audited Updated', 'email' => 'audited@example.com'])->assertOk();
        $this->actingAs($owner)->deleteJson("/api/v1/staff/{$id}")->assertOk();
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $owner->id, 'action' => 'staff.created', 'target_id' => $id]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $owner->id, 'action' => 'staff.updated', 'target_id' => $id]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $owner->id, 'action' => 'staff.deleted', 'target_id' => $id]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());
        return $user;
    }
}
