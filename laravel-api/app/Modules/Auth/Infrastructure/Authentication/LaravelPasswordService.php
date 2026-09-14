<?php

namespace App\Modules\Auth\Infrastructure\Authentication;

use App\Modules\Auth\Domain\Contracts\PasswordServiceInterface;
use Illuminate\Support\Facades\Hash;

final class LaravelPasswordService implements PasswordServiceInterface
{
    public function check(string $plain, string $hashed): bool
    {
        return Hash::check($plain, $hashed);
    }
}
