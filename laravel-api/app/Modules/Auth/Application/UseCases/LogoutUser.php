<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;

final class LogoutUser
{
    public function __construct(private readonly AuthenticationServiceInterface $authentication)
    {
    }

    public function execute(): void
    {
        $this->authentication->logout();
    }
}
