<?php

use App\Modules\Content\Presentation\Http\Controllers\ContentController;
use Illuminate\Support\Facades\Route;

Route::get('content', [ContentController::class, 'publicIndex'])->name('content.public.index');
Route::get('content/{slug}', [ContentController::class, 'publicShow'])->where('slug', '[a-z0-9][a-z0-9-]*')->name('content.public.show');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('admin/content', [ContentController::class, 'index'])->name('content.admin.index');
    Route::post('admin/content', [ContentController::class, 'store'])->name('content.admin.store');
    Route::get('admin/content/{content}', [ContentController::class, 'show'])->whereNumber('content')->name('content.admin.show');
    Route::match(['put', 'patch'], 'admin/content/{content}', [ContentController::class, 'update'])->whereNumber('content')->name('content.admin.update');
    Route::delete('admin/content/{content}', [ContentController::class, 'destroy'])->whereNumber('content')->name('content.admin.destroy');
    Route::post('admin/content/{content}/publish', [ContentController::class, 'publish'])->whereNumber('content')->name('content.admin.publish');
    Route::post('admin/content/{content}/unpublish', [ContentController::class, 'unpublish'])->whereNumber('content')->name('content.admin.unpublish');
    Route::get('admin/content/{content}/preview', [ContentController::class, 'preview'])->whereNumber('content')->name('content.admin.preview');
});
