<?php

namespace ShhStore\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;

class StoreSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $navigationGroup = 'Store';

    protected static ?int $navigationSort = 98;

    protected static ?string $title = 'Store Settings';

    protected static string $view = 'shh-store::filament.pages.store-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = $this->loadSettings();

        $this->form->fill([
            'stripe_key' => $settings['stripe_key'] ?? '',
            'stripe_secret' => $settings['stripe_secret'] ?? '',
            'stripe_webhook_secret' => $settings['stripe_webhook_secret'] ?? '',
            'paypal_client_id' => $settings['paypal_client_id'] ?? '',
            'paypal_client_secret' => $settings['paypal_client_secret'] ?? '',
            'paypal_mode' => $settings['paypal_mode'] ?? 'sandbox',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Stripe')
                    ->description('Configure your Stripe payment gateway credentials.')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        TextInput::make('stripe_key')
                            ->label('Publishable Key')
                            ->placeholder('pk_live_...')
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                        TextInput::make('stripe_secret')
                            ->label('Secret Key')
                            ->placeholder('sk_live_...')
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                        TextInput::make('stripe_webhook_secret')
                            ->label('Webhook Secret')
                            ->placeholder('whsec_...')
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                    ])
                    ->columns(1),

                Section::make('PayPal')
                    ->description('Configure your PayPal payment gateway credentials.')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        TextInput::make('paypal_client_id')
                            ->label('Client ID')
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                        TextInput::make('paypal_client_secret')
                            ->label('Client Secret')
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                        Select::make('paypal_mode')
                            ->label('Mode')
                            ->options([
                                'sandbox' => 'Sandbox (Testing)',
                                'live' => 'Live (Production)',
                            ])
                            ->default('sandbox'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $encrypted = [];

        foreach (['stripe_key', 'stripe_secret', 'stripe_webhook_secret', 'paypal_client_id', 'paypal_client_secret'] as $key) {
            $value = $state[$key] ?? '';
            $encrypted[$key] = $value !== '' ? Crypt::encryptString($value) : '';
        }

        $encrypted['paypal_mode'] = $state['paypal_mode'] ?? 'sandbox';

        $path = plugin_path('shh-store', 'settings.json');

        File::put($path, json_encode($encrypted, JSON_PRETTY_PRINT));

        // Refresh runtime config
        $this->applyToConfig($state);

        Notification::make()
            ->title('Settings saved')
            ->body('Payment gateway credentials have been stored.')
            ->success()
            ->send();
    }

    protected function loadSettings(): array
    {
        $path = plugin_path('shh-store', 'settings.json');

        if (!File::exists($path)) {
            return [];
        }

        $raw = json_decode(File::get($path), true);

        if (!is_array($raw)) {
            return [];
        }

        $decrypted = [];

        foreach (['stripe_key', 'stripe_secret', 'stripe_webhook_secret', 'paypal_client_id', 'paypal_client_secret'] as $key) {
            $value = $raw[$key] ?? '';
            if ($value !== '') {
                try {
                    $decrypted[$key] = Crypt::decryptString($value);
                } catch (\Exception $e) {
                    $decrypted[$key] = '';
                }
            } else {
                $decrypted[$key] = '';
            }
        }

        $decrypted['paypal_mode'] = $raw['paypal_mode'] ?? 'sandbox';

        return $decrypted;
    }

    protected function applyToConfig(array $settings): void
    {
        config([
            'shh-store.stripe.key' => $settings['stripe_key'] ?? '',
            'shh-store.stripe.secret' => $settings['stripe_secret'] ?? '',
            'shh-store.stripe.webhook_secret' => $settings['stripe_webhook_secret'] ?? '',
            'shh-store.paypal.client_id' => $settings['paypal_client_id'] ?? '',
            'shh-store.paypal.client_secret' => $settings['paypal_client_secret'] ?? '',
            'shh-store.paypal.mode' => $settings['paypal_mode'] ?? 'sandbox',
        ]);
    }
}
