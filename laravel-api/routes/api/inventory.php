<?php

use App\Modules\Inventory\Presentation\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::post('inventory/reserve', [InventoryController::class, 'reserve'])->name('inventory.reserve');
    Route::post('inventory/release', [InventoryController::class, 'release'])->name('inventory.release');
});
