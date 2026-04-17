<?php

namespace ShhStore\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use ShhStore\Http\Controllers\PaymentController;
use ShhStore\Livewire\Checkout;
use ShhStore\Livewire\ProductDetail;
use ShhStore\Livewire\StorePage;

class ShhStoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            plugin_path('shh-store', 'config', 'shh-store.php'),
            'shh-store'
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(
            plugin_path('shh-store', 'resources', 'views'),
            'shh-store'
        );

        $this->loadMigrationsFrom(
            plugin_path('shh-store', 'database', 'migrations')
        );

        $this->registerRoutes();
        $this->registerLivewireComponents();
    }

    protected function registerRoutes(): void
    {
        Route::middleware('web')->group(function () {
            Route::get('/storestaging', StorePage::class)->name('shh-store.store');
            Route::get('/storestaging/product/{slug}', ProductDetail::class)->name('shh-store.product');
            Route::get('/storestaging/checkout/{slug}/{cycle?}', Checkout::class)->name('shh-store.checkout');

            Route::get('/storestaging/payment/success/{order}', [PaymentController::class, 'success'])->name('shh-store.payment.success');
            Route::get('/storestaging/payment/cancel/{order}', [PaymentController::class, 'cancel'])->name('shh-store.payment.cancel');
            Route::get('/storestaging/payment/paypal/capture/{order}', [PaymentController::class, 'paypalCapture'])->name('shh-store.paypal.capture');
        });

        Route::post('/webhooks/shh-store/stripe', [PaymentController::class, 'stripeWebhook'])
            ->name('shh-store.webhooks.stripe')
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('shh-store::store-page', StorePage::class);
        Livewire::component('shh-store::product-detail', ProductDetail::class);
        Livewire::component('shh-store::checkout', Checkout::class);
    }
}
