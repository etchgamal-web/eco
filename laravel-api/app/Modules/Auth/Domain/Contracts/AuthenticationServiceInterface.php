<?php

namespace App\Modules\Auth\Domain\Contracts;

interface AuthenticationServiceInterface
{
    public function attempt(string $identifier, string $password, bool $remember = false): ?object;

    public function login(object $user, bool $remember = false): void;

    public function logout(): void;

    public function user(): ?object;
}
