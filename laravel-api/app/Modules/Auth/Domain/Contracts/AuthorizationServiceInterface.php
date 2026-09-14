<?php

namespace App\Modules\Auth\Domain\Contracts;

interface AuthorizationServiceInterface
{
    public function allows(object $user, string $permission): bool;
}
