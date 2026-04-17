<?php

namespace App\Plugins\ShadowStore\Providers;

use App\Plugins\ShadowStore\Console\Commands\ProcessBillingCommand;
use App\Plugins\ShadowStore\Services\StorefrontContentService;
use App\Plugins\ShadowStore\Models\StoreSetting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Throwable;

class ShadowStoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/store.php', 'shadow-store');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->ensureWritableDirectories();

        $this->applyRuntimeStoreSettings();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessBillingCommand::class,
            ]);
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('shadow-store:process-billing')->everyFifteenMinutes()->withoutOverlapping();
        });

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'shadow-store');

        View::composer('shadow-store::pages.*', function ($view) {
            /** @var StorefrontContentService $storefrontContent */
            $storefrontContent = app(StorefrontContentService::class);

            $view->with('storeHeader', $storefrontContent->getHeaderSettings());
            $view->with('storeAnnouncement', $storefrontContent->getAnnouncementSettings());
            $view->with('storePromo', $storefrontContent->getPromoSettings());
        });
        
        // Load routes
        Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');
    }

    protected function applyRuntimeStoreSettings(): void
    {
        try {
            if (!Schema::hasTable('store_settings')) {
                return;
            }

            config()->set('shadow-store.stripe.enabled', $this->toBool(StoreSetting::getValue('stripe_enabled', config('shadow-store.stripe.enabled', false))));
            config()->set('shadow-store.stripe.key', (string) StoreSetting::getValue('stripe_key', config('shadow-store.stripe.key', '')));
            config()->set('shadow-store.stripe.secret', (string) StoreSetting::getValue('stripe_secret', config('shadow-store.stripe.secret', '')));
            config()->set('shadow-store.stripe.webhook_secret', (string) StoreSetting::getValue('stripe_webhook_secret', config('shadow-store.stripe.webhook_secret', '')));

            config()->set('shadow-store.paypal.enabled', $this->toBool(StoreSetting::getValue('paypal_enabled', config('shadow-store.paypal.enabled', false))));
            config()->set('shadow-store.paypal.client_id', (string) StoreSetting::getValue('paypal_client_id', config('shadow-store.paypal.client_id', '')));
            config()->set('shadow-store.paypal.client_secret', (string) StoreSetting::getValue('paypal_client_secret', config('shadow-store.paypal.client_secret', '')));
            config()->set('shadow-store.paypal.sandbox', $this->toBool(StoreSetting::getValue('paypal_sandbox', config('shadow-store.paypal.sandbox', true))));

            config()->set('shadow-store.tax_rate', StoreSetting::getValue('tax_rate', config('shadow-store.tax_rate', 0)));
            config()->set('shadow-store.currency', (string) StoreSetting::getValue('currency', config('shadow-store.currency', 'USD')));
            config()->set('shadow-store.webhooks.enabled', $this->toBool(StoreSetting::getValue('webhook_enabled', config('shadow-store.webhooks.enabled', false))));
            config()->set('shadow-store.webhooks.url', (string) StoreSetting::getValue('webhook_url', config('shadow-store.webhooks.url', '')));
            config()->set('shadow-store.webhooks.mention', (string) StoreSetting::getValue('webhook_mention', config('shadow-store.webhooks.mention', '')));

            config()->set('shadow-store.billing_notifications.templates.due.subject', (string) StoreSetting::getValue('billing_due_subject', config('shadow-store.billing_notifications.templates.due.subject', '')));
            config()->set('shadow-store.billing_notifications.templates.due.body', (string) StoreSetting::getValue('billing_due_body', config('shadow-store.billing_notifications.templates.due.body', '')));
            config()->set('shadow-store.billing_notifications.templates.past_due.subject', (string) StoreSetting::getValue('billing_past_due_subject', config('shadow-store.billing_notifications.templates.past_due.subject', '')));
            config()->set('shadow-store.billing_notifications.templates.past_due.body', (string) StoreSetting::getValue('billing_past_due_body', config('shadow-store.billing_notifications.templates.past_due.body', '')));
            config()->set('shadow-store.billing_notifications.templates.suspended.subject', (string) StoreSetting::getValue('billing_suspended_subject', config('shadow-store.billing_notifications.templates.suspended.subject', '')));
            config()->set('shadow-store.billing_notifications.templates.suspended.body', (string) StoreSetting::getValue('billing_suspended_body', config('shadow-store.billing_notifications.templates.suspended.body', '')));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    protected function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    protected function ensureWritableDirectories(): void
    {
        $paths = [
            storage_path('framework/cache/data'),
            storage_path('framework/livewire-tmp'),
            storage_path('app/public/store/media'),
            storage_path('app/public/store/editor'),
        ];

        foreach ($paths as $path) {
            File::ensureDirectoryExists($path, 0755, true);
        }
    }
}
