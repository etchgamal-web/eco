<?php

use App\Modules\Staff\Presentation\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
    Route::match(['put', 'patch'], 'staff/{staffId}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('staff/{staffId}', [StaffController::class, 'destroy'])->name('staff.destroy');
});
