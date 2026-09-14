<?php

namespace App\Modules\Auth\Infrastructure\Authentication;

use App\Models\User;
use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Contracts\PasswordServiceInterface;
use App\Modules\Auth\Domain\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;

final class LaravelSessionAuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordServiceInterface $passwords,
    ) {
    }

    private function guard(): StatefulGuard
    {
        /** @var StatefulGuard $guard */
        $guard = Auth::guard('web');

        return $guard;
    }

    public function attempt(string $identifier, string $password, bool $remember = false): ?object
    {
        $user = $this->users->findByIdentifier($identifier);

        if ($user === null
            || !$user->isActive()
            || !$this->passwords->check($password, (string) $user->getAuthPassword())) {
            return null;
        }

        $this->guard()->login($user, $remember);

        return $this->guard()->user();
    }

    public function login(object $user, bool $remember = false): void
    {
        $this->guard()->login($user, $remember);
    }

    public function logout(): void
    {
        $this->guard()->logout();
    }

    public function user(): ?object
    {
        /** @var User|null $user */
        $user = $this->guard()->user();

        return $user !== null && $user->exists && $user->isActive() ? $user : null;
    }
}
