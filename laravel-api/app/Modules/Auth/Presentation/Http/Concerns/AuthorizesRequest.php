<?php

namespace App\Modules\Auth\Presentation\Http\Concerns;

use App\Models\User;
use App\Modules\Auth\Application\UseCases\AuthenticateUser;
use App\Modules\Auth\Application\UseCases\AuthorizeUser;

trait AuthorizesRequest
{
    protected function authenticatedUser(): User
    {
        return app(AuthenticateUser::class)->execute();
    }

    protected function authorizePermission(string $permission): bool
    {
        $user = $this->authenticatedUser();

        return app(AuthorizeUser::class)->execute($user, $permission);
    }
}
