<?php

namespace ShhStore;

use App\Contracts\Plugins\HasPluginSettings;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use ShhStore\Filament\Resources\StoreCategoryResource;
use ShhStore\Filament\Resources\StoreOrderResource;
use ShhStore\Filament\Resources\StoreProductResource;

class ShhStorePlugin implements HasPluginSettings, Plugin
{
    use EnvironmentWriterTrait;

    public function getId(): string
    {
        return 'shh-store';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            StoreCategoryResource::class,
            StoreProductResource::class,
            StoreOrderResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function getSettingsForm(): array
    {
        return [
            Section::make('Stripe')
                ->description('Configure your Stripe payment gateway credentials.')
                ->icon('heroicon-o-credit-card')
                ->columns(1)
                ->schema([
                    TextInput::make('stripe_key')
                        ->label('Publishable Key')
                        ->placeholder('pk_live_...')
                        ->password()
                        ->revealable()
                        ->maxLength(255)
                        ->default(fn () => config('shh-store.stripe.key')),
                    TextInput::make('stripe_secret')
                        ->label('Secret Key')
                        ->placeholder('sk_live_...')
                        ->password()
                        ->revealable()
                        ->maxLength(255)
                        ->default(fn () => config('shh-store.stripe.secret')),
                    TextInput::make('stripe_webhook_secret')
                        ->label('Webhook Secret')
                        ->placeholder('whsec_...')
                        ->password()
                        ->revealable()
                        ->maxLength(255)
                        ->default(fn () => config('shh-store.stripe.webhook_secret')),
                ]),
            Section::make('PayPal')
                ->description('Configure your PayPal payment gateway credentials.')
                ->icon('heroicon-o-banknotes')
                ->columns(1)
                ->schema([
                    TextInput::make('paypal_client_id')
                        ->label('Client ID')
                        ->password()
                        ->revealable()
                        ->maxLength(255)
                        ->default(fn () => config('shh-store.paypal.client_id')),
                    TextInput::make('paypal_client_secret')
                        ->label('Client Secret')
                        ->password()
                        ->revealable()
                        ->maxLength(255)
                        ->default(fn () => config('shh-store.paypal.client_secret')),
                    Select::make('paypal_mode')
                        ->label('Mode')
                        ->options([
                            'sandbox' => 'Sandbox (Testing)',
                            'live' => 'Live (Production)',
                        ])
                        ->default(fn () => config('shh-store.paypal.mode', 'sandbox')),
                ]),
        ];
    }

    public function saveSettings(array $data): void
    {
        $this->writeToEnvironment([
            'SHH_STRIPE_KEY' => $data['stripe_key'] ?? '',
            'SHH_STRIPE_SECRET' => $data['stripe_secret'] ?? '',
            'SHH_STRIPE_WEBHOOK_SECRET' => $data['stripe_webhook_secret'] ?? '',
            'SHH_PAYPAL_CLIENT_ID' => $data['paypal_client_id'] ?? '',
            'SHH_PAYPAL_CLIENT_SECRET' => $data['paypal_client_secret'] ?? '',
            'SHH_PAYPAL_MODE' => $data['paypal_mode'] ?? 'sandbox',
        ]);

        Notification::make()
            ->title('Settings saved')
            ->body('Payment gateway credentials have been written to .env.')
            ->success()
            ->send();
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
