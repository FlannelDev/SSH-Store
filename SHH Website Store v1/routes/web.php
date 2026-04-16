<?php

use App\Http\Controllers\PaymentController;
use App\Livewire\Checkout;
use App\Livewire\ProductDetail;
use App\Livewire\StorePage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('store');
});

Route::get('/store', StorePage::class)->name('store');
Route::get('/store/product/{slug}', ProductDetail::class)->name('store.product');

// Checkout
Route::get('/checkout/{slug}/{cycle?}', Checkout::class)->name('checkout');

// Payment callbacks
Route::get('/payment/success/{order}', [PaymentController::class, 'success'])->name('checkout.success');
Route::get('/payment/cancel/{order}', [PaymentController::class, 'cancel'])->name('checkout.cancel');
Route::get('/payment/paypal/capture/{order}', [PaymentController::class, 'paypalCapture'])->name('paypal.capture');

// Webhooks (excluded from CSRF)
Route::post('/webhooks/stripe', [PaymentController::class, 'stripeWebhook'])->name('webhooks.stripe');
