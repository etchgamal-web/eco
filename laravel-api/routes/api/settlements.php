<?php

use App\Modules\Settlement\Presentation\Http\Controllers\SettlementController;
use App\Modules\Settlement\Presentation\Http\Controllers\SettlementReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('reports/settlements')->group(function (): void {
    Route::get('summary', [SettlementReportController::class, 'summary'])->name('reports.settlements.summary');
    Route::get('providers/export', [SettlementReportController::class, 'providersExport'])->name('reports.settlements.providers.export');
    Route::get('providers', [SettlementReportController::class, 'providers'])->name('reports.settlements.providers');
});
Route::middleware('auth:sanctum')->prefix('shipping/settlements')->group(function (): void {
    Route::get('', [SettlementController::class, 'index'])->name('shipping.settlements.index');
    Route::post('import', [SettlementController::class, 'import'])->name('shipping.settlements.import');
    Route::get('{id}', [SettlementController::class, 'show'])->name('shipping.settlements.show');
    Route::get('{id}/items', [SettlementController::class, 'items'])->name('shipping.settlements.items');
    Route::get('{id}/export', [SettlementController::class, 'export'])->name('shipping.settlements.export');
    Route::patch('{id}/finalize', [SettlementController::class, 'finalize'])->name('shipping.settlements.finalize');
});
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('settings/settlement-import', [SettlementController::class, 'settings'])->name('settings.settlement-import.index');
    Route::put('settings/settlement-import', [SettlementController::class, 'updateSetting'])->name('settings.settlement-import.update');
});
