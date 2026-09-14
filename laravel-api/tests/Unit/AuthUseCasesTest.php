<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Auth\Domain\ValueObjects\ChangePasswordData;
use App\Modules\Auth\Domain\ValueObjects\RegisterUserData;
use App\Modules\Auth\Application\UseCases\ChangePassword;
use App\Modules\Auth\Application\UseCases\AuthorizeUser;
use App\Modules\Auth\Application\UseCases\AuthenticateUser;
use App\Modules\Auth\Application\UseCases\LoginUser;
use App\Modules\Auth\Application\UseCases\LogoutUser;
use App\Modules\Auth\Application\UseCases\RegisterUser;
use App\Modules\Auth\Domain\Contracts\AuthenticationServiceInterface;
use App\Modules\Auth\Domain\Contracts\AuthorizationServiceInterface;
use App\Modules\Auth\Domain\Contracts\PasswordServiceInterface;
use App\Modules\Auth\Domain\Contracts\UserRepositoryInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationException;
use PHPUnit\Framework\TestCase;

class AuthUseCasesTest extends TestCase
{
    public function test_authentication_use_case_returns_current_user(): void
    {
        $user = new User(['name' => 'Authenticated']);
        $authentication = $this->createMock(AuthenticationServiceInterface::class);
        $authentication->expects(self::once())->method('user')->willReturn($user);

        self::assertSame($user, (new AuthenticateUser($authentication))->execute());
    }

    public function test_authentication_use_case_rejects_missing_user(): void
    {
        $authentication = $this->createMock(AuthenticationServiceInterface::class);
        $authentication->expects(self::once())->method('user')->willReturn(null);

        $this->expectException(AuthenticationException::class);
        (new AuthenticateUser($authentication))->execute();
    }

    public function test_authorization_delegates_to_domain_contract(): void
    {
        $authorization = $this->createMock(AuthorizationServiceInterface::class);
        $user = new User(['name' => 'Authorized']);
        $authorization->expects(self::once())->method('allows')->with($user, 'products.create')->willReturn(true);

        self::assertTrue((new AuthorizeUser($authorization))->execute($user, 'products.create'));
    }

    public function test_registration_delegates_to_user_repository(): void
    {
        $data = new RegisterUserData('Customer', 'customer@example.com', null, 'password');
        $user = new User(['name' => 'Customer']);
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects(self::once())->method('create')->with($data)->willReturn($user);

        self::assertSame($user, (new RegisterUser($repository))->execute($data));
    }

    public function test_invalid_login_is_rejected(): void
    {
        $authentication = $this->createMock(AuthenticationServiceInterface::class);
        $authentication->method('attempt')->willReturn(null);

        $this->expectException(AuthenticationException::class);
        (new LoginUser($authentication))->execute('customer@example.com', 'wrong');
    }

    public function test_logout_delegates_to_authentication_service(): void
    {
        $authentication = $this->createMock(AuthenticationServiceInterface::class);
        $authentication->expects(self::once())->method('logout');

        (new LogoutUser($authentication))->execute();
    }

    public function test_change_password_requires_current_password(): void
    {
        $user = new User();
        $user->setRawAttributes(['password' => 'hashed']);
        $passwords = $this->createMock(PasswordServiceInterface::class);
        $passwords->method('check')->willReturn(false);

        $this->expectException(AuthenticationException::class);
        (new ChangePassword($this->createMock(UserRepositoryInterface::class), $passwords))
            ->execute($user, new ChangePasswordData('wrong', 'new-password'));
    }
}
