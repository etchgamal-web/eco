<?php

namespace App\Modules\Auth\Domain\Contracts;

interface PasswordServiceInterface
{
    public function check(string $plain, string $hashed): bool;

    public function sendResetLink(array $credentials): string;

    public function reset(array $credentials, callable $callback): string;
}
