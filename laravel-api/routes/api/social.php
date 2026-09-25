<?php

use App\Modules\SocialCommerce\Presentation\Http\Controllers\SocialAutomationRuleController;
use App\Modules\SocialCommerce\Presentation\Http\Controllers\SocialConnectionController;
use App\Modules\SocialCommerce\Presentation\Http\Controllers\SocialConversationController;
use App\Modules\SocialCommerce\Presentation\Http\Controllers\SocialInteractionController;
use App\Modules\SocialCommerce\Presentation\Http\Controllers\SocialMessageTemplateController;
use App\Modules\SocialCommerce\Presentation\Http\Controllers\SocialWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('social/webhooks/{channel}', [SocialWebhookController::class, 'verify'])->whereIn('channel', ['facebook', 'instagram', 'whatsapp'])->name('social.webhooks.verify');
Route::post('social/webhooks/{channel}', [SocialWebhookController::class, 'receive'])->whereIn('channel', ['facebook', 'instagram', 'whatsapp'])->middleware('throttle:social-webhook')->name('social.webhooks.receive');
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('admin/social/connections', [SocialConnectionController::class, 'index'])->name('social.connections.index');
    Route::post('admin/social/connections', [SocialConnectionController::class, 'store'])->name('social.connections.store');
    Route::get('admin/social/connections/{connection}', [SocialConnectionController::class, 'show'])->name('social.connections.show');
    Route::match(['put', 'patch'], 'admin/social/connections/{connection}', [SocialConnectionController::class, 'update'])->name('social.connections.update');
    Route::delete('admin/social/connections/{connection}', [SocialConnectionController::class, 'destroy'])->name('social.connections.destroy');
    Route::get('admin/social/interactions', [SocialInteractionController::class, 'index'])->name('social.interactions.index');
    Route::post('admin/social/interactions/{interaction}/reply', [SocialInteractionController::class, 'reply'])->name('social.interactions.reply');
    Route::get('admin/social/conversations/{conversation}', [SocialConversationController::class, 'show'])->name('social.conversations.show');
    Route::get('admin/social/conversations/{conversation}/messages', [SocialConversationController::class, 'messages'])->name('social.conversations.messages');
    Route::post('admin/social/conversations/{conversation}/messages', [SocialConversationController::class, 'send'])->name('social.conversations.messages.store');
    Route::post('admin/social/conversations/{conversation}/pause', [SocialConversationController::class, 'pause'])->name('social.conversations.pause');
    Route::post('admin/social/conversations/{conversation}/resume', [SocialConversationController::class, 'resume'])->name('social.conversations.resume');
    Route::post('admin/social/conversations/{conversation}/manual', [SocialConversationController::class, 'manual'])->name('social.conversations.manual');
    Route::get('admin/social/templates', [SocialMessageTemplateController::class, 'index'])->name('social.templates.index');
    Route::post('admin/social/templates', [SocialMessageTemplateController::class, 'store'])->name('social.templates.store');
    Route::match(['put', 'patch'], 'admin/social/templates/{template}', [SocialMessageTemplateController::class, 'update'])->name('social.templates.update');
    Route::delete('admin/social/templates/{template}', [SocialMessageTemplateController::class, 'destroy'])->name('social.templates.destroy');
    Route::post('admin/social/templates/{template}/preview', [SocialMessageTemplateController::class, 'preview'])->name('social.templates.preview');
    Route::get('admin/social/automation-rules', [SocialAutomationRuleController::class, 'index'])->name('social.automation.index');
    Route::post('admin/social/automation-rules', [SocialAutomationRuleController::class, 'store'])->name('social.automation.store');
    Route::match(['put', 'patch'], 'admin/social/automation-rules/{rule}', [SocialAutomationRuleController::class, 'update'])->name('social.automation.update');
    Route::delete('admin/social/automation-rules/{rule}', [SocialAutomationRuleController::class, 'destroy'])->name('social.automation.destroy');
});
