<?php

use App\Modules\Payment\Presentation\Http\Controllers\PaymobWebhookController;
use App\Modules\Payment\Presentation\Http\Controllers\KashierWebhookController;
use App\Modules\Shipping\Presentation\Http\Controllers\BostaWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/paymob', PaymobWebhookController::class)->middleware('throttle:payment-webhook')->name('webhooks.paymob');
Route::post('webhooks/kashier', KashierWebhookController::class)->middleware('throttle:payment-webhook')->name('webhooks.kashier');
Route::post('webhooks/bosta', BostaWebhookController::class)->middleware('throttle:shipping-webhook')->name('webhooks.bosta');
