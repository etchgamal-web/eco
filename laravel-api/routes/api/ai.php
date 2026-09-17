<?php

use App\Modules\AI\Presentation\Http\Controllers\AiSettingsController;
use App\Modules\AI\Presentation\Http\Controllers\ProductAiController;
use App\Modules\AI\Presentation\Http\Controllers\SocialReplyAiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('admin/ai/settings', [AiSettingsController::class, 'show'])->name('ai.settings.show');
    Route::match(['put', 'patch'], 'admin/ai/settings', [AiSettingsController::class, 'update'])->name('ai.settings.update');
    Route::post('admin/ai/products/draft', [ProductAiController::class, 'draft'])->name('ai.products.draft');
    Route::post('admin/ai/social/reply-suggestion', [SocialReplyAiController::class, 'suggestion'])->name('ai.social.reply-suggestion');
});
