<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissions = [
        ['name' => 'View Sales Reports', 'slug' => 'reports.view', 'group' => 'reports'],
        ['name' => 'View Integrations', 'slug' => 'integrations.view', 'group' => 'integrations'],
        ['name' => 'Retry Integrations', 'slug' => 'integrations.retry', 'group' => 'integrations'],
        ['name' => 'View Roles', 'slug' => 'roles.view', 'group' => 'authorization'],
        ['name' => 'Create Roles', 'slug' => 'roles.create', 'group' => 'authorization'],
        ['name' => 'Update Roles', 'slug' => 'roles.update', 'group' => 'authorization'],
        ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'group' => 'authorization'],
        ['name' => 'Manage Permissions', 'slug' => 'permissions.manage', 'group' => 'authorization'],
        ['name' => 'View Customers', 'slug' => 'customers.view', 'group' => 'customers'],
        ['name' => 'Update Customers', 'slug' => 'customers.update', 'group' => 'customers'],
        ['name' => 'View Payments', 'slug' => 'payments.view', 'group' => 'payments'],
        ['name' => 'Manage Payments', 'slug' => 'payments.manage', 'group' => 'payments'],
        ['name' => 'Refund Payments', 'slug' => 'payments.refund', 'group' => 'payments'],
    ];

    public function up(): void
    {
        $now = now();
        foreach ($this->permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                [...$permission, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        $slugs = array_column($this->permissions, 'slug');
        $ids = DB::table('permissions')->whereIn('slug', $slugs)->pluck('id');
        if (Schema::hasTable('permission_role') && $ids->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
        }
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
