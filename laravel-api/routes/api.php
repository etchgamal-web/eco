<?php

use App\Http\Controllers\DashboardController;
use App\Modules\Reporting\Presentation\Http\Controllers\SalesAnalyticsController;
use Illuminate\Support\Facades\Route;

// Public API contract. Legacy /api routes are intentionally not registered.
Route::prefix('v1')->group(function (): void {
    require __DIR__.'/api/auth.php';
    require __DIR__.'/api/webhooks.php';
    require __DIR__.'/api/customer.php';
    require __DIR__.'/api/customer-admin.php';
    require __DIR__.'/api/catalog.php';
    require __DIR__.'/api/content.php';
    require __DIR__.'/api/inventory.php';
    require __DIR__.'/api/orders.php';
    Route::middleware('auth:sanctum')->get('dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    require __DIR__.'/api/monitoring.php';
    require __DIR__.'/api/settlements.php';
    Route::get('reports/sales', SalesAnalyticsController::class)->middleware('auth:sanctum')->name('reports.sales');
    require __DIR__.'/api/payments.php';
    require __DIR__.'/api/shipping.php';
    require __DIR__.'/api/promotion.php';
    require __DIR__.'/api/staff.php';
    require __DIR__.'/api/integrations.php';
    require __DIR__.'/api/settings.php';
    require __DIR__.'/api/social.php';
    require __DIR__.'/api/landing.php';
    require __DIR__.'/api/ai.php';
});
