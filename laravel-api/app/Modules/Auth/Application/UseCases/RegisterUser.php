<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\UserRepositoryInterface;
use App\Modules\Auth\Domain\ValueObjects\RegisterUserData;

final class RegisterUser
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function execute(RegisterUserData $data): object
    {
        return $this->users->create($data);
    }
}
