<?php

use Illuminate\Support\Facades\Route;

// Public API contract. Legacy /api routes are intentionally not registered.
Route::prefix('v1')->group(function (): void {
    require __DIR__.'/api/auth.php';
    require __DIR__.'/api/webhooks.php';
    require __DIR__.'/api/customer.php';
    require __DIR__.'/api/catalog.php';
    require __DIR__.'/api/inventory.php';
    require __DIR__.'/api/orders.php';
    require __DIR__.'/api/payments.php';
    require __DIR__.'/api/shipping.php';
    require __DIR__.'/api/promotion.php';
    require __DIR__.'/api/staff.php';
    require __DIR__.'/api/settings.php';
});
