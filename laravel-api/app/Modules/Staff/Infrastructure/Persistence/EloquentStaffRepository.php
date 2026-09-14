<?php

namespace App\Modules\Staff\Infrastructure\Persistence;

use App\Models\Role;
use App\Models\User;
use App\Modules\Staff\Domain\ValueObjects\StaffData;
use App\Modules\Staff\Domain\Contracts\StaffRepositoryInterface;
use App\Modules\Staff\Domain\Exceptions\StaffActionNotAllowedException;
use App\Modules\Staff\Domain\Exceptions\StaffNotFoundException;
use Illuminate\Support\Facades\Hash;

final class EloquentStaffRepository implements StaffRepositoryInterface
{
    public function list(): mixed
    {
        return User::query()->whereDoesntHave('roles', fn ($q) => $q->where('slug', 'customer'))->with('roles')->latest()->paginate(20);
    }

    public function create(StaffData $data): User
    {
        $user = User::query()->create([
            'name' => $data->name, 'email' => $data->email, 'phone' => $data->phone,
            'status' => $data->status, 'password' => Hash::make((string) $data->password),
        ]);
        $this->syncRoles($user, $data->roleSlugs);
        return $user->load('roles');
    }

    public function update(object $staff, StaffData $data): User
    {
        $staff->update([
            'name' => $data->name, 'email' => $data->email, 'phone' => $data->phone, 'status' => $data->status,
            ...($data->password !== null ? ['password' => Hash::make($data->password)] : []),
        ]);
        if ($data->rolesProvided) {
            $this->syncRoles($staff, $data->roleSlugs);
        }
        return $staff->fresh()->load('roles');
    }

    public function delete(object $staff): void
    {
        if (! $staff->exists) throw new StaffNotFoundException('Staff user not found.');
        $staff->delete();
    }

    public function find(int $id): User
    {
        $user = User::query()->whereKey($id)->whereDoesntHave('roles', fn ($q) => $q->where('slug', 'customer'))->first();
        if ($user === null) throw new StaffNotFoundException('Staff user not found.');
        return $user;
    }

    public function activeOwnerCount(): int
    {
        return User::query()->where('status', 'active')->whereHas('roles', fn ($query) => $query->where('slug', 'owner')->where('is_active', true))->count();
    }

    private function syncRoles(object $user, array $slugs): void
    {
        $roles = Role::query()->whereIn('slug', $slugs)->get();
        if ($roles->contains(fn (Role $role): bool => ! $role->is_active)) {
            throw new StaffActionNotAllowedException('Inactive roles cannot be assigned.');
        }
        $user->roles()->sync($roles->pluck('id'));
    }
}
