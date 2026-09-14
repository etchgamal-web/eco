<?php
namespace Tests;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createAdminUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', 'admin')->firstOrFail());

        return $user;
    }

    /** @param array<int, string> $permissionSlugs */
    protected function createUserWithPermissions(array $permissionSlugs): User
    {
        $user = User::factory()->create();
        $permissions = Permission::query()->whereIn('slug', $permissionSlugs)->get();

        $user->permissionOverrides()->attach(
            $permissions->mapWithKeys(
                static fn (Permission $permission): array => [$permission->id => ['allowed' => true]],
            )->all(),
        );

        return $user;
    }
}
