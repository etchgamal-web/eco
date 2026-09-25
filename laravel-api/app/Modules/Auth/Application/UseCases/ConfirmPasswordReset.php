<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\PasswordServiceInterface;
use App\Modules\Auth\Domain\Contracts\UserRepositoryInterface;

final class ConfirmPasswordReset
{
    public function __construct(
        private readonly PasswordServiceInterface $passwords,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function execute(array $data): string
    {
        return $this->passwords->reset($data, function (object $user, string $password): void {
            $this->users->updatePassword($user, $password);
        });
    }
}
