<?php

use App\Modules\Settings\Presentation\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('settings/groups/{group}', [SettingsController::class, 'group'])->name('settings.group');
    Route::get('settings/{key}', [SettingsController::class, 'show'])->name('settings.show');
    Route::put('settings/{key?}', [SettingsController::class, 'update'])->name('settings.update');
});
