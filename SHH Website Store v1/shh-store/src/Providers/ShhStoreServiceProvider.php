<?php

namespace ShhStore\Providers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
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
        $this->loadSavedSettings();
        $this->registerRoutes();
        $this->registerLivewireComponents();
    }

    protected function loadSavedSettings(): void
    {
        $path = plugin_path('shh-store', 'settings.json');

        if (!File::exists($path)) {
            return;
        }

        $raw = json_decode(File::get($path), true);

        if (!is_array($raw)) {
            return;
        }

        $keys = ['stripe_key', 'stripe_secret', 'stripe_webhook_secret', 'paypal_client_id', 'paypal_client_secret'];
        $map = [
            'stripe_key' => 'shh-store.stripe.key',
            'stripe_secret' => 'shh-store.stripe.secret',
            'stripe_webhook_secret' => 'shh-store.stripe.webhook_secret',
            'paypal_client_id' => 'shh-store.paypal.client_id',
            'paypal_client_secret' => 'shh-store.paypal.client_secret',
        ];

        foreach ($keys as $key) {
            $value = $raw[$key] ?? '';
            if ($value !== '' && empty(config($map[$key]))) {
                try {
                    config([$map[$key] => Crypt::decryptString($value)]);
                } catch (\Exception $e) {
                    // Skip invalid encrypted values
                }
            }
        }

        if (!empty($raw['paypal_mode']) && empty(env('SHH_PAYPAL_MODE'))) {
            config(['shh-store.paypal.mode' => $raw['paypal_mode']]);
        }
    }

    protected function registerRoutes(): void
    {
        Route::middleware('web')->group(function () {
            Route::get('/store', StorePage::class)->name('shh-store.store');
            Route::get('/store/product/{slug}', ProductDetail::class)->name('shh-store.product');
            Route::get('/store/checkout/{slug}/{cycle?}', Checkout::class)->name('shh-store.checkout');

            Route::get('/store/payment/success/{order}', [PaymentController::class, 'success'])->name('shh-store.payment.success');
            Route::get('/store/payment/cancel/{order}', [PaymentController::class, 'cancel'])->name('shh-store.payment.cancel');
            Route::get('/store/payment/paypal/capture/{order}', [PaymentController::class, 'paypalCapture'])->name('shh-store.paypal.capture');
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
