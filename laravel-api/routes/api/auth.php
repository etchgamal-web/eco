<?php

use App\Modules\Auth\Presentation\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('auth/register', [AuthController::class, 'register'])
    ->middleware(['guest', 'throttle:auth-register'])
    ->name('auth.register');
Route::post('auth/login', [AuthController::class, 'login'])
    ->middleware(['guest', 'throttle:auth-login'])
    ->name('auth.login');

Route::middleware('auth')->group(function (): void {
    Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('auth/password', [AuthController::class, 'changePassword'])
        ->middleware('throttle:password-change')
        ->name('auth.password');
});
