<?php

namespace ShhStore\Providers;

use App\Models\Role;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use ShhStore\Http\Controllers\PaymentController;
use ShhStore\Livewire\Checkout;
use ShhStore\Livewire\ProductDetail;
use ShhStore\Livewire\StorePage;
use ShhStore\Console\Commands\BumpPluginVersionCommand;
use ShhStore\Console\Commands\ProcessUnpaidSuspensionsCommand;
use ShhStore\Models\StoreCategory;
use ShhStore\Models\StoreOrder;
use ShhStore\Models\StoreProduct;
use ShhStore\Policies\StoreCategoryPolicy;
use ShhStore\Policies\StoreOrderPolicy;
use ShhStore\Policies\StoreProductPolicy;

class ShhStoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/shh-store.php',
            'shh-store'
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(
            __DIR__ . '/../../resources/views',
            'shh-store'
        );

        $this->loadMigrationsFrom(
            __DIR__ . '/../../database/migrations'
        );

        $this->ensureTablesExist();

        if ($this->app->runningInConsole()) {
            $this->commands([
                BumpPluginVersionCommand::class,
                ProcessUnpaidSuspensionsCommand::class,
            ]);
        }

        $this->registerPermissions();
        $this->registerPolicies();
        $this->registerRoutes();
        $this->registerLivewireComponents();
    }

    protected function registerPermissions(): void
    {
        Role::registerCustomDefaultPermissions('storeCategory');
        Role::registerCustomDefaultPermissions('storeProduct');
        Role::registerCustomDefaultPermissions('storeOrder');
        Role::registerCustomPermissions([
            'storeSetting' => ['view', 'update'],
        ]);

        Role::registerCustomModelIcon('storeCategory', 'heroicon-o-tag');
        Role::registerCustomModelIcon('storeProduct', 'heroicon-o-server-stack');
        Role::registerCustomModelIcon('storeOrder', 'heroicon-o-shopping-cart');
        Role::registerCustomModelIcon('storeSetting', 'heroicon-o-cog-6-tooth');
    }

    protected function registerPolicies(): void
    {
        Gate::policy(StoreCategory::class, StoreCategoryPolicy::class);
        Gate::policy(StoreProduct::class, StoreProductPolicy::class);
        Gate::policy(StoreOrder::class, StoreOrderPolicy::class);
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

    protected function ensureTablesExist(): void
    {
        try {
            if (!Schema::hasTable('shh_store_categories')) {
                Schema::create('shh_store_categories', function (Blueprint $table) {
                    $table->id();
                    $table->string('name');
                    $table->string('slug')->unique();
                    $table->text('description')->nullable();
                    $table->string('icon')->nullable();
                    $table->integer('sort_order')->default(0);
                    $table->boolean('is_visible')->default(true);
                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('shh_store_products')) {
                Schema::create('shh_store_products', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('category_id')->constrained('shh_store_categories')->cascadeOnDelete();
                    $table->string('name');
                    $table->string('slug')->unique();
                    $table->text('description')->nullable();
                    $table->string('tier')->nullable();
                    $table->string('cpu')->nullable();
                    $table->string('ram')->nullable();
                    $table->string('storage')->nullable();
                    $table->decimal('price_monthly', 8, 2);
                    $table->decimal('price_quarterly', 8, 2)->nullable();
                    $table->decimal('price_annually', 8, 2)->nullable();
                    $table->boolean('is_featured')->default(false);
                    $table->boolean('is_visible')->default(true);
                    $table->boolean('in_stock')->default(true);
                    $table->integer('sort_order')->default(0);
                    $table->json('features')->nullable();
                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('shh_store_orders')) {
                Schema::create('shh_store_orders', function (Blueprint $table) {
                    $table->id();
                    $table->string('order_number')->unique();
                    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                    $table->foreignId('product_id')->constrained('shh_store_products')->cascadeOnDelete();
                    $table->string('billing_cycle');
                    $table->decimal('amount', 8, 2);
                    $table->string('currency', 3)->default('USD');
                    $table->string('status')->default('pending');
                    $table->string('payment_method')->nullable();
                    $table->string('payment_id')->nullable();
                    $table->string('transaction_id')->nullable();
                    $table->string('customer_email');
                    $table->string('customer_name')->nullable();
                    $table->json('meta')->nullable();
                    $table->timestamp('paid_at')->nullable();
                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('shh_store_settings')) {
                Schema::create('shh_store_settings', function (Blueprint $table) {
                    $table->id();
                    $table->string('key')->unique();
                    $table->longText('value')->nullable();
                    $table->boolean('is_encrypted')->default(false);
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
