<?php

namespace App\Modules\Customer\Infrastructure\Persistence;

use App\Models\User;
use App\Modules\Customer\Domain\Contracts\CustomerRepositoryInterface;

final class EloquentCustomerRepository implements CustomerRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::query()->find($id);
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
}
