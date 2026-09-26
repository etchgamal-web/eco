<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\UserRepositoryInterface;

final class UpdateProfile
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    /** @param array{name: string, email: string} $data */
    public function execute(object $user, array $data): object
    {
        return $this->users->updateProfile($user, $data);
    }
}
