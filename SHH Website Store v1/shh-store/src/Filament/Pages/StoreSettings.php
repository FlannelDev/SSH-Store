<?php

namespace ShhStore\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use ShhStore\Models\StoreSetting;

class StoreSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?int $navigationSort = 10;
    protected string $view = 'shh-store::filament.pages.store-settings';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return 'Store';
    }

    public static function getNavigationLabel(): string
    {
        return 'Settings';
    }

    public function getTitle(): string
    {
        return 'SHH Store Settings';
    }

    public function mount(): void
    {
        $this->data = [
            // Stripe
            'stripe_enabled' => $this->boolSetting('stripe_enabled', false),
            'stripe_key' => (string) StoreSetting::getValue('stripe_key', (string) config('shh-store.stripe.key', '')),
            'stripe_secret' => '',
            'stripe_webhook_secret' => '',
            'has_stripe_secret' => (string) StoreSetting::getValue('stripe_secret', '') !== '',
            'has_stripe_webhook_secret' => (string) StoreSetting::getValue('stripe_webhook_secret', '') !== '',

            // PayPal
            'paypal_enabled' => $this->boolSetting('paypal_enabled', false),
            'paypal_client_id' => (string) StoreSetting::getValue('paypal_client_id', (string) config('shh-store.paypal.client_id', '')),
            'paypal_client_secret' => '',
            'paypal_sandbox' => $this->boolSetting('paypal_sandbox', true),
            'has_paypal_client_secret' => (string) StoreSetting::getValue('paypal_client_secret', '') !== '',

            // General
            'currency' => (string) StoreSetting::getValue('currency', 'USD'),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('settings_tabs')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Stripe')
                            ->id('stripe')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Section::make('Stripe Configuration')
                                    ->description('Accept credit/debit card payments via Stripe')
                                    ->schema([
                                        Toggle::make('stripe_enabled')
                                            ->label('Enable Stripe Payments'),
                                        TextInput::make('stripe_key')
                                            ->label('Publishable Key')
                                            ->placeholder('pk_live_...')
                                            ->helperText('Your Stripe publishable key (starts with pk_)'),
                                        TextInput::make('stripe_secret')
                                            ->label('Secret Key')
                                            ->password()
                                            ->placeholder('sk_live_...')
                                            ->helperText('Leave blank to keep current secret. Enter a value to replace.'),
                                        TextInput::make('stripe_webhook_secret')
                                            ->label('Webhook Secret')
                                            ->password()
                                            ->placeholder('whsec_...')
                                            ->helperText('Leave blank to keep current secret. Enter a value to replace.'),
                                    ]),
                            ]),

                        Tab::make('PayPal')
                            ->id('paypal')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Section::make('PayPal Configuration')
                                    ->description('Accept PayPal payments')
                                    ->schema([
                                        Toggle::make('paypal_enabled')
                                            ->label('Enable PayPal Payments'),
                                        TextInput::make('paypal_client_id')
                                            ->label('Client ID')
                                            ->placeholder('Your PayPal Client ID'),
                                        TextInput::make('paypal_client_secret')
                                            ->label('Client Secret')
                                            ->password()
                                            ->placeholder('Your PayPal Client Secret')
                                            ->helperText('Leave blank to keep current secret. Enter a value to replace.'),
                                        Toggle::make('paypal_sandbox')
                                            ->label('Sandbox Mode')
                                            ->helperText('Enable for testing with sandbox credentials'),
                                    ]),
                            ]),

                        Tab::make('General')
                            ->id('general')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('General Settings')
                                    ->schema([
                                        Select::make('currency')
                                            ->label('Currency')
                                            ->options([
                                                'USD' => 'USD — US Dollar',
                                                'EUR' => 'EUR — Euro',
                                                'GBP' => 'GBP — British Pound',
                                                'CAD' => 'CAD — Canadian Dollar',
                                                'AUD' => 'AUD — Australian Dollar',
                                            ])
                                            ->default('USD'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('shh_store_settings')) {
            Notification::make()
                ->danger()
                ->title('Migration Required')
                ->body('The shh_store_settings table does not exist. Please run php artisan migrate.')
                ->send();

            return;
        }

        $data = $this->data;

        StoreSetting::setValue('stripe_enabled', !empty($data['stripe_enabled']) ? '1' : '0');
        StoreSetting::setValue('stripe_key', trim((string) ($data['stripe_key'] ?? '')));
        if (!empty($data['stripe_secret'])) {
            StoreSetting::setValue('stripe_secret', trim((string) $data['stripe_secret']), true);
        }
        if (!empty($data['stripe_webhook_secret'])) {
            StoreSetting::setValue('stripe_webhook_secret', trim((string) $data['stripe_webhook_secret']), true);
        }

        StoreSetting::setValue('paypal_enabled', !empty($data['paypal_enabled']) ? '1' : '0');
        StoreSetting::setValue('paypal_client_id', trim((string) ($data['paypal_client_id'] ?? '')));
        if (!empty($data['paypal_client_secret'])) {
            StoreSetting::setValue('paypal_client_secret', trim((string) $data['paypal_client_secret']), true);
        }
        StoreSetting::setValue('paypal_sandbox', !empty($data['paypal_sandbox']) ? '1' : '0');

        StoreSetting::setValue('currency', strtoupper(trim((string) ($data['currency'] ?? 'USD'))));

        $this->mount();

        Notification::make()
            ->success()
            ->title('Settings Saved')
            ->body('Your store settings have been saved to the database successfully.')
            ->send();
    }

    protected function boolSetting(string $key, bool $default): bool
    {
        $raw = StoreSetting::getValue($key, $default ? '1' : '0');

        if (is_bool($raw)) {
            return $raw;
        }

        return in_array((string) $raw, ['1', 'true', 'on', 'yes'], true);
    }
}
