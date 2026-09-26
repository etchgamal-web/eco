<?php

use App\Modules\Customer\Presentation\Http\Controllers\CustomerAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('admin/customers', [CustomerAdminController::class, 'index'])->name('admin.customers.index');
    Route::get('admin/customers/{customerId}', [CustomerAdminController::class, 'show'])->name('admin.customers.show');
});
