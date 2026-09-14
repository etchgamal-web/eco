<?php

use App\Modules\Promotion\Presentation\Http\Controllers\CouponController;
use App\Modules\Tax\Presentation\Http\Controllers\TaxRuleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::apiResource('coupons', CouponController::class)->parameters(['coupons' => 'couponId']);
    Route::apiResource('tax-rules', TaxRuleController::class)->parameters(['tax-rules' => 'taxRuleId']);
});
