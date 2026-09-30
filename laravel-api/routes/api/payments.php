<?php

use App\Modules\Payment\Presentation\Http\Controllers\OperationalDashboardController;
use App\Modules\Payment\Presentation\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('orders/{orderId}/payments', [PaymentController::class, 'adminIndex'])->name('payments.index');
    Route::get('operations/dashboard', OperationalDashboardController::class)->name('operations.dashboard');
    Route::post('operations/payments/{paymentId}/reconcile', [OperationalDashboardController::class, 'reconcile'])->name('operations.reconcile');
    Route::post('operations/outbox/{eventId}/retry', [OperationalDashboardController::class, 'retryOutbox'])->name('operations.outbox.retry');
    Route::post('payments/{paymentId}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
    Route::post('payments/{paymentId}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
});
