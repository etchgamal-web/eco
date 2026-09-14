<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException;

final class LoginUser
{
    public function __construct(private readonly AuthenticationServiceInterface $authentication)
    {
    }

    public function execute(string $identifier, string $password, bool $remember = false): object
    {
        $user = $this->authentication->attempt($identifier, $password, $remember);

        if ($user === null) {
            throw new AuthenticationException('The provided credentials are invalid.');
        }

        return $user;
    }
}
