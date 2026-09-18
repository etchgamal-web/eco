<?php

use App\Modules\LandingPage\Presentation\Http\Controllers\LandingAnalyticsController;
use App\Modules\LandingPage\Presentation\Http\Controllers\LandingLeadController;
use App\Modules\LandingPage\Presentation\Http\Controllers\LandingPageController;
use App\Modules\LandingPage\Presentation\Http\Controllers\PublicLandingPageController;
use Illuminate\Support\Facades\Route;

Route::get('landing-pages/{slug}', [PublicLandingPageController::class, 'show'])->middleware('throttle:api')->name('landing.public.show');
Route::post('landing-pages/{slug}/leads', [PublicLandingPageController::class, 'lead'])->middleware('throttle:landing-lead')->name('landing.public.leads');
Route::post('landing-pages/{slug}/events', [LandingAnalyticsController::class, 'event'])->middleware('throttle:landing-event')->name('landing.public.events');
Route::middleware('auth')->group(function () {
    Route::get('admin/landing-pages', [LandingPageController::class, 'index'])->name('landing.index');
    Route::post('admin/landing-pages', [LandingPageController::class, 'store'])->name('landing.store');
    Route::match(['put', 'patch'], 'admin/landing-pages/{page}', [LandingPageController::class, 'update'])->name('landing.update');
    Route::delete('admin/landing-pages/{page}', [LandingPageController::class, 'destroy'])->name('landing.destroy');
    Route::post('admin/landing-pages/{page}/publish', [LandingPageController::class, 'publish'])->name('landing.publish');
    Route::post('admin/landing-pages/{page}/unpublish', [LandingPageController::class, 'unpublish'])->name('landing.unpublish');
    Route::get('admin/landing-leads', [LandingLeadController::class, 'index'])->name('landing.leads.index');
    Route::match(['put', 'patch'], 'admin/landing-leads/{lead}', [LandingLeadController::class, 'update'])->name('landing.leads.update');
    Route::get('admin/landing-pages/{page}/stats', [LandingAnalyticsController::class, 'stats'])->name('landing.stats');
});
