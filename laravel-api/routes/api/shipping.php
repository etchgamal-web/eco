<?php

use App\Modules\Shipping\Presentation\Http\Controllers\ShippingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('shipping-methods', [ShippingController::class, 'index'])->name('shipping-methods.index');
    Route::get('shipping-methods/{shippingMethodId}', [ShippingController::class, 'show'])->name('shipping-methods.show');
    Route::post('shipping-methods', [ShippingController::class, 'store'])->name('shipping-methods.store');
    Route::match(['put', 'patch'], 'shipping-methods/{shippingMethodId}', [ShippingController::class, 'update'])->name('shipping-methods.update');
    Route::delete('shipping-methods/{shippingMethodId}', [ShippingController::class, 'destroy'])->name('shipping-methods.destroy');
    Route::patch('shipments/{shipmentId}/status', [ShippingController::class, 'status'])->name('shipments.status');
});
