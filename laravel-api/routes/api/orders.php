<?php

use App\Modules\Order\Presentation\Http\Controllers\OrderController;
use App\Modules\Order\Presentation\Http\Controllers\ReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{orderId}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{orderId}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('orders/{orderId}/shipping-charge', [OrderController::class, 'setShippingCharge'])->name('orders.shipping-charge');
    Route::post('orders/{orderId}/review', [OrderController::class, 'review'])->name('orders.review');
    Route::post('orders/{orderId}/contact', [OrderController::class, 'contact'])->name('orders.contact');
    Route::post('orders/{orderId}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('orders/{orderId}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('returns', [ReturnController::class, 'index'])->name('returns.index');
    Route::patch('returns/{returnId}/approve', [ReturnController::class, 'approve'])->name('returns.approve');
    Route::patch('returns/{returnId}/reject', [ReturnController::class, 'reject'])->name('returns.reject');
});
