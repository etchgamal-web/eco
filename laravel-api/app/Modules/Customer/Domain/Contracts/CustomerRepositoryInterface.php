<?php

namespace App\Modules\Customer\Domain\Contracts;

interface CustomerRepositoryInterface
{
    public function findById(int $id): ?object;

    public function update(object $customer, string $name, ?string $email, ?string $phone): object;
}
