<?php

use Illuminate\Support\Facades\Route;
use App\Plugins\ShadowStore\Http\Controllers\StoreController;
use App\Plugins\ShadowStore\Http\Controllers\CartController;
use App\Plugins\ShadowStore\Http\Controllers\CheckoutController;
use App\Plugins\ShadowStore\Http\Controllers\StorefrontAdminController;
use App\Plugins\ShadowStore\Http\Controllers\WebhookController;

// Store routes (public)
Route::get('/store', [StoreController::class, 'index'])->name('store.index');
Route::get('/store/msa', [StoreController::class, 'msa'])->name('store.msa');
Route::get('/store/product/{product:slug}', [StoreController::class, 'show'])->name('store.product');

// Cart routes
Route::get('/store/cart', [CartController::class, 'index'])->name('store.cart');
Route::post('/store/cart/add', [CartController::class, 'add'])->name('store.cart.add');
Route::post('/store/cart/update', [CartController::class, 'update'])->name('store.cart.update');
Route::post('/store/cart/remove', [CartController::class, 'remove'])->name('store.cart.remove');

// Checkout routes (auth required)
Route::middleware('auth')->group(function () {
    Route::get('/store/checkout', [CheckoutController::class, 'index'])->name('store.checkout');
    Route::post('/store/checkout/process', [CheckoutController::class, 'process'])->name('store.checkout.process');
    Route::get('/store/checkout/success', [CheckoutController::class, 'success'])->name('store.checkout.success');
    Route::get('/store/checkout/cancel', [CheckoutController::class, 'cancel'])->name('store.checkout.cancel');
    Route::get('/store/orders', [CheckoutController::class, 'orders'])->name('store.orders');
    Route::get('/store/billing', [CheckoutController::class, 'billing'])->name('store.billing');
    Route::post('/store/billing/make-payment', [CheckoutController::class, 'makePayment'])->name('store.billing.make-payment');
    Route::post('/store/checkout/coupon', [CheckoutController::class, 'applyCoupon'])->name('store.checkout.coupon');
    Route::delete('/store/checkout/coupon', [CheckoutController::class, 'removeCoupon'])->name('store.checkout.coupon.remove');
    Route::post('/store/checkout/proration', [CheckoutController::class, 'applyProration'])->name('store.checkout.proration');
    Route::delete('/store/checkout/proration', [CheckoutController::class, 'removeProration'])->name('store.checkout.proration.remove');

    Route::post('/store/admin/editor/{section}', [StorefrontAdminController::class, 'updateSection'])->name('store.admin.editor.section');
    Route::post('/store/admin/editor/block/{block}', [StorefrontAdminController::class, 'updateBlock'])->name('store.admin.editor.block');
    Route::post('/store/admin/editor/reorder', [StorefrontAdminController::class, 'reorderBlocks'])->name('store.admin.editor.reorder');
    Route::post('/store/admin/media/upload', [StorefrontAdminController::class, 'uploadMedia'])->name('store.admin.media.upload');
});

// Webhook routes (no CSRF, no auth)
Route::post('/store/webhook/stripe', [WebhookController::class, 'stripe'])
    ->middleware('throttle:120,1')
    ->withoutMiddleware(['web'])
    ->name('store.webhook.stripe');

Route::post('/store/webhook/paypal', [WebhookController::class, 'paypal'])
    ->middleware('throttle:120,1')
    ->withoutMiddleware(['web'])
    ->name('store.webhook.paypal');

// Dedicated Machines
Route::get('/store/dedicated', [App\Plugins\ShadowStore\Http\Controllers\StoreController::class, 'dedicated'])->name('store.dedicated');
Route::get('/store/dedicated/{slug}', [App\Plugins\ShadowStore\Http\Controllers\StoreController::class, 'showDedicated'])->name('store.dedicated.show');
