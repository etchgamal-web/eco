<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\PasswordServiceInterface;

final class ConfirmPasswordReset
{
    public function __construct(private readonly PasswordServiceInterface $passwords) {}

    public function execute(array $data): string
    {
        return $this->passwords->reset($data, static function (object $user, string $password): void {
            $user->forceFill(['password' => $password, 'remember_token' => null])->save();
        });
    }
}
