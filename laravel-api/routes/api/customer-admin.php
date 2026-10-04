<?php

use App\Modules\Customer\Presentation\Http\Controllers\CustomerAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('admin/customers', [CustomerAdminController::class, 'index'])->name('admin.customers.index');
    Route::get('admin/customers/export', [CustomerAdminController::class, 'export'])->name('admin.customers.export');
    Route::get('admin/customers/{customerId}', [CustomerAdminController::class, 'show'])->name('admin.customers.show');
    Route::match(['put', 'patch'], 'admin/customers/{customerId}', [CustomerAdminController::class, 'update'])->name('admin.customers.update');
    Route::post('admin/customers/{customerId}/addresses', [CustomerAdminController::class, 'storeAddress'])->name('admin.customers.addresses.store');
    Route::match(['put', 'patch'], 'admin/customers/{customerId}/addresses/{addressId}', [CustomerAdminController::class, 'updateAddress'])->name('admin.customers.addresses.update');
    Route::delete('admin/customers/{customerId}/addresses/{addressId}', [CustomerAdminController::class, 'destroyAddress'])->name('admin.customers.addresses.destroy');
});
