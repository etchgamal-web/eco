<?php

use App\Modules\Shipping\Presentation\Http\Controllers\PublicTrackingController;
use App\Modules\Shipping\Presentation\Http\Controllers\ShippingController;
use Illuminate\Support\Facades\Route;

Route::get('public/shipments/{tracking_token}', [PublicTrackingController::class, 'show'])
    ->middleware('throttle:public-tracking')
    ->name('public.shipments.track');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('shipping-methods', [ShippingController::class, 'index'])->name('shipping-methods.index');
    Route::get('shipping-methods/{shippingMethodId}', [ShippingController::class, 'show'])->name('shipping-methods.show');
    Route::post('shipping-methods', [ShippingController::class, 'store'])->name('shipping-methods.store');
    Route::match(['put', 'patch'], 'shipping-methods/{shippingMethodId}', [ShippingController::class, 'update'])->name('shipping-methods.update');
    Route::delete('shipping-methods/{shippingMethodId}', [ShippingController::class, 'destroy'])->name('shipping-methods.destroy');
    Route::post('orders/{orderId}/shipments', [ShippingController::class, 'createShipment'])->name('shipments.store');
    Route::patch('shipments/{shipmentId}/status', [ShippingController::class, 'status'])->name('shipments.status');
});
