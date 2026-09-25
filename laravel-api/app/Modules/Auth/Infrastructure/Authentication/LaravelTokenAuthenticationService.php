<?php

namespace App\Modules\Auth\Infrastructure\Authentication;

use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Contracts\PasswordServiceInterface;
use App\Modules\Auth\Domain\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Auth;

final class LaravelTokenAuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordServiceInterface $passwords,
    ) {}

    private function guard(): Guard
    {
        return Auth::guard('sanctum');
    }

    public function attempt(string $identifier, string $password, bool $remember = false): ?object
    {
        $user = $this->users->findByIdentifier($identifier);

        if ($user === null
            || ! $user->isActive()
            || ! $this->passwords->check($password, (string) $user->getAuthPassword())) {
            return null;
        }

        return $user;
    }

    public function login(object $user, bool $remember = false): void
    {
        // API clients receive an explicit token from the controller.
    }

    public function logout(): void
    {
        $user = $this->guard()->user();
        $token = $user?->currentAccessToken();
        if ($token !== null && method_exists($token, 'delete')) {
            $token->delete();
        }

        Auth::guard('web')->logout();
        Auth::guard('sanctum')->forgetUser();
    }

    public function user(): ?object
    {
        $user = $this->guard()->user();

        return $user !== null && $user->exists && $user->isActive() ? $user : null;
    }
}
