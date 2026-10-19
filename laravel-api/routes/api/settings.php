<?php

use App\Modules\Settings\Presentation\Http\Controllers\PublicAppearanceController;
use App\Modules\Settings\Presentation\Http\Controllers\PublicCheckoutSettingsController;
use App\Modules\Settings\Presentation\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

// Public: tells the storefront which theme to render (chosen from the admin dashboard).
Route::get('public/appearance', [PublicAppearanceController::class, 'show'])->middleware('throttle:api')->name('appearance.public');
Route::get('public/checkout-settings', [PublicCheckoutSettingsController::class, 'show'])->middleware('throttle:api')->name('checkout-settings.public');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('settings/groups/{group}', [SettingsController::class, 'group'])->name('settings.group');
    Route::get('settings/{key}', [SettingsController::class, 'show'])->name('settings.show');
    Route::put('settings/{key?}', [SettingsController::class, 'update'])->name('settings.update');
});
