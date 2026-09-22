<?php
use App\Modules\Monitoring\Presentation\Http\Controllers\MonitoringController;
use Illuminate\Support\Facades\Route;
Route::middleware('auth')->group(function():void{
 Route::get('settings/order-monitoring',[MonitoringController::class,'settings'])->name('settings.order-monitoring.index');
 Route::put('settings/order-monitoring',[MonitoringController::class,'updateSetting'])->name('settings.order-monitoring.update');
 Route::get('orders/delayed',[MonitoringController::class,'delayed'])->name('orders.delayed');
 Route::get('operational-alerts',[MonitoringController::class,'alerts'])->name('operational-alerts.index');
 Route::get('operational-alerts/{id}',[MonitoringController::class,'alert'])->name('operational-alerts.show');
 Route::patch('operational-alerts/{id}/acknowledge',[MonitoringController::class,'acknowledge'])->name('operational-alerts.acknowledge');
 Route::patch('operational-alerts/{id}/resolve',[MonitoringController::class,'resolve'])->name('operational-alerts.resolve');
});
