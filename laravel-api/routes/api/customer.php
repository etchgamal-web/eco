<?php

use App\Modules\Catalog\Presentation\Http\Controllers\ProductReviewController;
use App\Modules\Customer\Presentation\Http\Controllers\CustomerController;
use App\Modules\Customer\Presentation\Http\Controllers\CustomerFeaturesController;
use App\Modules\Order\Presentation\Http\Controllers\CheckoutController;
use App\Modules\Order\Presentation\Http\Controllers\OrderController;
use App\Modules\Order\Presentation\Http\Controllers\ReturnController;
use App\Modules\Payment\Presentation\Http\Controllers\PaymentController;
use App\Modules\Shipping\Presentation\Http\Controllers\ShippingController;
use Illuminate\Support\Facades\Route;

Route::post('customer/checkout', CheckoutController::class)
    ->middleware('throttle:checkout')
    ->name('customer.checkout');

Route::middleware('auth')->group(function (): void {
    Route::get('customer/profile', [CustomerController::class, 'profile'])->name('customer.profile');
    Route::match(['put', 'patch'], 'customer/profile', [CustomerController::class, 'updateProfile'])->name('customer.profile.update');
    Route::get('customer/addresses', [CustomerFeaturesController::class, 'addresses'])->name('customer.addresses.index');
    Route::get('customer/addresses/default', [CustomerFeaturesController::class, 'defaultAddress'])->name('customer.addresses.default');
    Route::post('customer/addresses', [CustomerFeaturesController::class, 'addAddress'])->name('customer.addresses.store');
    Route::match(['put', 'patch'], 'customer/addresses/{addressId}', [CustomerFeaturesController::class, 'updateAddress'])->name('customer.addresses.update');
    Route::delete('customer/addresses/{addressId}', [CustomerFeaturesController::class, 'deleteAddress'])->name('customer.addresses.destroy');
    Route::get('customer/orders', [OrderController::class, 'customerIndex'])->name('customer.orders.index');
    Route::get('customer/orders/{orderId}', [OrderController::class, 'customerShow'])->name('customer.orders.show');
    Route::post('customer/orders/{orderId}/cancel', [OrderController::class, 'customerCancel'])->name('customer.orders.cancel');
    Route::get('customer/returns', [ReturnController::class, 'customerIndex'])->name('customer.returns.index');
    Route::post('customer/orders/{orderId}/returns', [ReturnController::class, 'store'])->middleware('throttle:return-create')->name('customer.returns.store');
    Route::post('customer/orders/{orderId}/payments', [PaymentController::class, 'store'])->middleware('throttle:payment-create')->name('customer.payments.store');
    Route::get('customer/orders/{orderId}/payments', [PaymentController::class, 'index'])->name('customer.payments.index');
    Route::get('customer/shipping-methods', [ShippingController::class, 'customerMethods'])->name('customer.shipping-methods.index');
    Route::get('customer/orders/{orderId}/shipments', [ShippingController::class, 'customerShipments'])->name('customer.shipments.index');
    Route::post('customer/orders/{orderId}/shipments', [ShippingController::class, 'createShipment'])->name('customer.shipments.store');
    Route::get('customer/cart', [CustomerFeaturesController::class, 'cart'])->name('customer.cart.show');
    Route::post('customer/cart/items', [CustomerFeaturesController::class, 'addCartItem'])->middleware('throttle:cart-mutation')->name('customer.cart.items.store');
    Route::patch('customer/cart/items', [CustomerFeaturesController::class, 'updateCartItem'])->middleware('throttle:cart-mutation')->name('customer.cart.items.update');
    Route::delete('customer/cart', [CustomerFeaturesController::class, 'clearCart'])->middleware('throttle:cart-mutation')->name('customer.cart.clear');
    Route::delete('customer/cart/items/{productId}/{variantId?}', [CustomerFeaturesController::class, 'removeCartItem'])->middleware('throttle:cart-mutation')->name('customer.cart.items.destroy');
    Route::get('customer/wishlist', [CustomerFeaturesController::class, 'wishlist'])->name('customer.wishlist.index');
    Route::post('customer/wishlist', [CustomerFeaturesController::class, 'addWishlist'])->name('customer.wishlist.store');
    Route::delete('customer/wishlist/{productId}', [CustomerFeaturesController::class, 'removeWishlist'])->name('customer.wishlist.destroy');
    Route::get('customer/preferences', [CustomerFeaturesController::class, 'preferences'])->name('customer.preferences.show');
    Route::put('customer/preferences', [CustomerFeaturesController::class, 'updatePreferences'])->name('customer.preferences.update');
    Route::get('customer/notifications', [CustomerFeaturesController::class, 'notifications'])->name('customer.notifications.index');
    Route::patch('customer/notifications/{notificationId}/read', [CustomerFeaturesController::class, 'readNotification'])->name('customer.notifications.read');
    Route::get('products/{productId}/reviews', [ProductReviewController::class, 'index'])->name('customer.reviews.index');
    Route::post('products/{productId}/reviews', [ProductReviewController::class, 'store'])->name('customer.reviews.store');
    Route::get('reviews', [ProductReviewController::class, 'adminIndex'])->name('reviews.index');
    Route::patch('reviews/{reviewId}/moderate', [ProductReviewController::class, 'moderate'])->name('reviews.moderate');
});
