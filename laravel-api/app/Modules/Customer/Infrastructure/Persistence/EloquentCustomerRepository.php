<?php

namespace App\Modules\Customer\Infrastructure\Persistence;

use App\Modules\Auth\Infrastructure\Models\User;
use App\Modules\Customer\Domain\Contracts\CustomerRepositoryInterface;

final class EloquentCustomerRepository implements CustomerRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function findCustomerById(int $id): ?User
    {
        return User::query()->whereKey($id)->whereHas('roles', fn ($roles) => $roles->where('slug', 'customer'))->first();
    }

    public function update(object $customer, string $name, ?string $email, ?string $phone): User
    {
        $customer->forceFill([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
        ])->save();

        return $customer->fresh();
    }

    public function updateAdmin(object $customer, array $data): User
    {
        $customer->forceFill($data)->save();

        return $customer->fresh();
    }
}
