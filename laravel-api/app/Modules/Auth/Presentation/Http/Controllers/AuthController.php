<?php

namespace App\Modules\Auth\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Domain\ValueObjects\ChangePasswordData;
use App\Modules\Auth\Domain\ValueObjects\RegisterUserData;
use App\Modules\Auth\Application\UseCases\ChangePassword;
use App\Modules\Auth\Application\UseCases\GetCurrentUser;
use App\Modules\Auth\Application\UseCases\LoginUser;
use App\Modules\Auth\Application\UseCases\LogoutUser;
use App\Modules\Auth\Application\UseCases\RegisterUser;
use App\Modules\Auth\Presentation\Http\Requests\AuthRequest;
use App\Modules\Auth\Presentation\Http\Requests\ChangePasswordRequest;
use App\Modules\Auth\Presentation\Http\Requests\LoginRequest;
use App\Modules\Auth\Presentation\Http\Requests\RegisterRequest;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUser $useCase, LoginUser $login): JsonResponse
    {
        $user = $useCase->execute(RegisterUserData::fromArray($request->validated()));
        $login->execute($request->validated('email') ?? $request->validated('phone'), $request->validated('password'));

        return response()->json(['data' => $user], 201);
    }

    public function login(LoginRequest $request, LoginUser $useCase): JsonResponse
    {
        $user = $useCase->execute(
            $request->validated('identifier'),
            $request->validated('password'),
            (bool) $request->validated('remember', false),
        );

        return response()->json(['data' => $user]);
    }

    public function me(AuthRequest $request, GetCurrentUser $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute()]);
    }

    public function logout(AuthRequest $request, LogoutUser $useCase): JsonResponse
    {
        $useCase->execute();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function changePassword(ChangePasswordRequest $request, ChangePassword $useCase, GetCurrentUser $current): JsonResponse
    {
        $user = $useCase->execute($current->execute(), ChangePasswordData::fromArray($request->validated()));

        return response()->json(['data' => $user]);
    }
}
