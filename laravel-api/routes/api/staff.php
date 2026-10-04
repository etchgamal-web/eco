<?php

use App\Modules\Auth\Presentation\Http\Controllers\RbacController;
use App\Modules\Staff\Presentation\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
    Route::match(['put', 'patch'], 'staff/{staffId}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('staff/{staffId}', [StaffController::class, 'destroy'])->name('staff.destroy');
    Route::get('staff/{staffId}/audit', [StaffController::class, 'audit'])->name('staff.audit');
    Route::get('roles', [RbacController::class, 'index'])->name('roles.index');
    Route::match(['put', 'patch'], 'roles/{roleId}/permissions', [RbacController::class, 'update'])->name('roles.permissions.update');
    Route::patch('roles/{roleId}/status', [RbacController::class, 'status'])->name('roles.status.update');
    Route::get('roles/{roleId}/audit', [RbacController::class, 'audit'])->name('roles.audit');
});
