<?php

namespace App\Modules\Auth\Infrastructure\Authentication;

use App\Modules\Auth\Domain\Contracts\PasswordServiceInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

final class LaravelPasswordService implements PasswordServiceInterface
{
    public function check(string $plain, string $hashed): bool
    {
        return Hash::check($plain, $hashed);
    }

    public function sendResetLink(array $credentials): string
    {
        return (string) Password::sendResetLink($credentials);
    }

    public function reset(array $credentials, callable $callback): string
    {
        return (string) Password::reset($credentials, $callback);
    }
}
