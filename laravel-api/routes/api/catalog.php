<?php

use App\Modules\Catalog\Presentation\Http\Controllers\AttributeController;
use App\Modules\Catalog\Presentation\Http\Controllers\BrandController;
use App\Modules\Catalog\Presentation\Http\Controllers\CategoryController;
use App\Modules\Catalog\Presentation\Http\Controllers\ProductController;
use App\Modules\Catalog\Presentation\Http\Controllers\ProductMediaController;
use Illuminate\Support\Facades\Route;

// Storefront catalog is public; mutation and moderation routes remain protected below.
Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/{productId}', [ProductController::class, 'show'])->name('products.show');
Route::get('products/{productId}/variants', [ProductController::class, 'variants'])->name('products.variants.index');
Route::get('products/{productId}/variants/{variantId}', [ProductController::class, 'showVariant'])->name('products.variants.show');
Route::middleware('auth')->group(function (): void {
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::match(['put', 'patch'], 'products/{productId}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('products/{productId}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('products/{productId}/variants', [ProductController::class, 'storeVariant'])->name('products.variants.store');
    Route::match(['put', 'patch'], 'products/{productId}/variants/{variantId}', [ProductController::class, 'updateVariant'])->name('products.variants.update');
    Route::delete('products/{productId}/variants/{variantId}', [ProductController::class, 'destroyVariant'])->name('products.variants.destroy');
    Route::post('products/{productId}/media', [ProductMediaController::class, 'store'])->name('products.media.store');
    Route::delete('products/{productId}/media/{mediaId}', [ProductMediaController::class, 'destroy'])->name('products.media.destroy');
    Route::get('products/{productId}/media', [ProductMediaController::class, 'index'])->name('products.media.index');
    Route::patch('products/{productId}/media/{mediaId}/order', [ProductMediaController::class, 'reorder'])->name('products.media.reorder');
    Route::post('products/{productId}/variants/{variantId}/media', [ProductMediaController::class, 'variantStore'])->name('products.variants.media.store');
    Route::delete('products/{productId}/variants/{variantId}/media/{mediaId}', [ProductMediaController::class, 'variantDestroy'])->name('products.variants.media.destroy');
    Route::patch('products/{productId}/variants/{variantId}/media/{mediaId}/order', [ProductMediaController::class, 'variantReorder'])->name('products.variants.media.reorder');
    Route::get('products/{productId}/variants/{variantId}/media', [ProductMediaController::class, 'variantIndex'])->name('products.variants.media.index');
    Route::get('attributes', [AttributeController::class, 'index'])->name('attributes.index');
    Route::get('attributes/{attributeId}', [AttributeController::class, 'show'])->name('attributes.show');
    Route::get('attributes/{attributeId}/values', [AttributeController::class, 'values'])->name('attributes.values.index');
    Route::get('attributes/{attributeId}/values/{valueId}', [AttributeController::class, 'showValue'])->name('attributes.values.show');
    Route::post('attributes', [AttributeController::class, 'store'])->name('attributes.store');
    Route::match(['put', 'patch'], 'attributes/{attributeId}', [AttributeController::class, 'update'])->name('attributes.update');
    Route::delete('attributes/{attributeId}', [AttributeController::class, 'destroy'])->name('attributes.destroy');
    Route::post('attributes/{attributeId}/values', [AttributeController::class, 'storeValue'])->name('attributes.values.store');
    Route::match(['put', 'patch'], 'attributes/{attributeId}/values/{valueId}', [AttributeController::class, 'updateValue'])->name('attributes.values.update');
    Route::delete('attributes/{attributeId}/values/{valueId}', [AttributeController::class, 'destroyValue'])->name('attributes.values.destroy');
    Route::apiResource('brands', BrandController::class)->parameters(['brands' => 'brandId']);
    Route::apiResource('categories', CategoryController::class)->parameters(['categories' => 'categoryId']);
});
