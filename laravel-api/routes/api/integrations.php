<?php

use App\Modules\Integration\Presentation\Http\Controllers\IntegrationEventController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('integrations/events', [IntegrationEventController::class, 'index'])->name('integrations.events.index');
    Route::post('integrations/events/{source}/{id}/retry', [IntegrationEventController::class, 'retry'])->whereIn('source', ['payment', 'shipping', 'social'])->name('integrations.events.retry');
});
