<?php

namespace App\Modules\Auth\Domain\Contracts;

use App\Modules\Auth\Domain\ValueObjects\RegisterUserData;

interface UserRepositoryInterface
{
    public function findByIdentifier(string $identifier): ?object;

    public function findById(int $id): ?object;

    public function create(RegisterUserData $data): object;

    public function updatePassword(object $user, string $password): object;
}
