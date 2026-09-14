<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Domain\ValueObjects\ChangePasswordData;
use App\Modules\Auth\Domain\Contracts\PasswordServiceInterface;
use App\Modules\Auth\Domain\Contracts\UserRepositoryInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException;

final class ChangePassword
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordServiceInterface $passwords,
    ) {
    }

    public function execute(object $user, ChangePasswordData $data): object
    {
        if (!$this->passwords->check($data->currentPassword, $user->password)) {
            throw new AuthenticationException('The current password is invalid.');
        }

        return $this->users->updatePassword($user, $data->password);
    }
}
