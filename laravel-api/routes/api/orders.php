<?php

use App\Modules\Order\Presentation\Http\Controllers\OrderController;
use App\Modules\Order\Presentation\Http\Controllers\ReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/export', [OrderController::class, 'export'])->name('orders.export');
    Route::get('orders/{orderId}', [OrderController::class, 'show'])->whereNumber('orderId')->name('orders.show');
    Route::get('orders/{orderId}/timeline', [OrderController::class, 'timeline'])->whereNumber('orderId')->name('orders.timeline');
    Route::patch('orders/{orderId}/status', [OrderController::class, 'updateStatus'])->whereNumber('orderId')->name('orders.status');
    Route::patch('orders/{orderId}/shipping-charge', [OrderController::class, 'setShippingCharge'])->whereNumber('orderId')->name('orders.shipping-charge');
    Route::post('orders/{orderId}/review', [OrderController::class, 'review'])->whereNumber('orderId')->name('orders.review');
    Route::post('orders/{orderId}/contact', [OrderController::class, 'contact'])->whereNumber('orderId')->name('orders.contact');
    Route::post('orders/{orderId}/confirm', [OrderController::class, 'confirm'])->whereNumber('orderId')->name('orders.confirm');
    Route::post('orders/{orderId}/cancel', [OrderController::class, 'cancel'])->whereNumber('orderId')->name('orders.cancel');
    Route::get('returns', [ReturnController::class, 'index'])->name('returns.index');
    Route::patch('returns/{returnId}/approve', [ReturnController::class, 'approve'])->name('returns.approve');
    Route::patch('returns/{returnId}/receive', [ReturnController::class, 'receive'])->name('returns.receive');
    Route::patch('returns/{returnId}/inspect', [ReturnController::class, 'inspect'])->name('returns.inspect');
    Route::patch('returns/{returnId}/reject', [ReturnController::class, 'reject'])->name('returns.reject');
});
