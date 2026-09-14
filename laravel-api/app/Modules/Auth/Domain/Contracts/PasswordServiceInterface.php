<?php

namespace App\Modules\Auth\Domain\Contracts;

interface PasswordServiceInterface
{
    public function check(string $plain, string $hashed): bool;
}
