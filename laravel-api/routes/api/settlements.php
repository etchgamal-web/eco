<?php
use App\Modules\Settlement\Presentation\Http\Controllers\SettlementController;
use Illuminate\Support\Facades\Route;
Route::middleware('auth')->prefix('shipping/settlements')->group(function():void{Route::post('import',[SettlementController::class,'import'])->name('shipping.settlements.import');Route::get('{id}',[SettlementController::class,'show'])->name('shipping.settlements.show');Route::get('{id}/items',[SettlementController::class,'items'])->name('shipping.settlements.items');Route::patch('{id}/finalize',[SettlementController::class,'finalize'])->name('shipping.settlements.finalize');});
Route::middleware('auth')->group(function():void{Route::get('settings/settlement-import',[SettlementController::class,'settings'])->name('settings.settlement-import.index');Route::put('settings/settlement-import',[SettlementController::class,'updateSetting'])->name('settings.settlement-import.update');});
