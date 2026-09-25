<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\PasswordServiceInterface;

final class RequestPasswordReset
{
    public function __construct(private readonly PasswordServiceInterface $passwords) {}

    public function execute(string $email): string
    {
        return $this->passwords->sendResetLink(['email' => $email]);
    }
}
