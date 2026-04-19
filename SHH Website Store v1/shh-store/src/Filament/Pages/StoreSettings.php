<?php

namespace ShhStore\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view storeSetting') ?? false;
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
            'billing_suspend_after_days' => max(0, (int) StoreSetting::getValue('billing_suspend_after_days', (string) config('shh-store.billing.suspend_after_days', 2))),

            // Hero
            'hero_title' => (string) StoreSetting::getValue('hero_title', 'Game Servers'),
            'hero_subtitle' => (string) StoreSetting::getValue('hero_subtitle', '200+ supported games on Ryzen 9 9950X3D with NVMe storage, sensible density, and clean billing.'),
            'hero_cta_text' => (string) StoreSetting::getValue('hero_cta_text', 'Browse Catalog'),
            'hero_cta2_text' => (string) StoreSetting::getValue('hero_cta2_text', 'Join Discord'),

            // Hero Stats
            'hero_stat1_value' => (string) StoreSetting::getValue('hero_stat1_value', '$20'),
            'hero_stat1_label' => (string) StoreSetting::getValue('hero_stat1_label', 'Starting monthly'),
            'hero_stat2_value' => (string) StoreSetting::getValue('hero_stat2_value', '200+'),
            'hero_stat2_label' => (string) StoreSetting::getValue('hero_stat2_label', 'Games supported'),
            'hero_stat3_value' => (string) StoreSetting::getValue('hero_stat3_value', '9950X3D'),
            'hero_stat3_label' => (string) StoreSetting::getValue('hero_stat3_label', 'Ryzen CPU'),
            'hero_stat4_value' => (string) StoreSetting::getValue('hero_stat4_value', 'NVMe'),
            'hero_stat4_label' => (string) StoreSetting::getValue('hero_stat4_label', 'Fast storage'),

            // Featured Section
            'featured_heading' => (string) StoreSetting::getValue('featured_heading', 'Featured'),
            'featured_subtitle' => (string) StoreSetting::getValue('featured_subtitle', 'Hand-picked configurations for a quick start.'),

            // Catalog Section
            'catalog_heading' => (string) StoreSetting::getValue('catalog_heading', 'All Configurations'),

            // Features / Why Us
            'features_heading' => (string) StoreSetting::getValue('features_heading', 'Why Shadow Haven'),
            'feature1_title' => (string) StoreSetting::getValue('feature1_title', 'Fast Launch'),
            'feature1_desc' => (string) StoreSetting::getValue('feature1_desc', 'Provisioning and checkout wired to minimize the gap between payment and playable server.'),
            'feature2_title' => (string) StoreSetting::getValue('feature2_title', 'Clean Pricing'),
            'feature2_desc' => (string) StoreSetting::getValue('feature2_desc', 'Straightforward catalog, visible monthly costs, and consistent billing.'),
            'feature3_title' => (string) StoreSetting::getValue('feature3_title', '200+ Games'),
            'feature3_desc' => (string) StoreSetting::getValue('feature3_desc', 'A large supported game list with breadth and performance for niche communities.'),
            'feature4_title' => (string) StoreSetting::getValue('feature4_title', 'Real Hardware'),
            'feature4_desc' => (string) StoreSetting::getValue('feature4_desc', 'High-cache Ryzen 9 9950X3D CPU and NVMe storage tuned for game workloads.'),
            'feature5_title' => (string) StoreSetting::getValue('feature5_title', 'DDoS Protected'),
            'feature5_desc' => (string) StoreSetting::getValue('feature5_desc', 'DDoS mitigation and hardened infrastructure as the baseline, not an upsell.'),
            'feature6_title' => (string) StoreSetting::getValue('feature6_title', 'Unified Management'),
            'feature6_desc' => (string) StoreSetting::getValue('feature6_desc', 'Billing, payments, and server management in one clean storefront.'),

            // Product Detail
            'product_tagline' => (string) StoreSetting::getValue('product_tagline', 'Instant deployment · DDoS protected · Ryzen 9 9950X3D'),
            'product_cta_text' => (string) StoreSetting::getValue('product_cta_text', 'Configure & Deploy'),

            // Checkout
            'checkout_secure_text' => (string) StoreSetting::getValue('checkout_secure_text', 'SSL Encrypted · Secure Payments'),

            // Payment Pages
            'payment_success_title' => (string) StoreSetting::getValue('payment_success_title', 'Payment Successful'),
            'payment_success_message' => (string) StoreSetting::getValue('payment_success_message', 'Your server will be provisioned shortly.'),
            'payment_cancel_title' => (string) StoreSetting::getValue('payment_cancel_title', 'Payment Cancelled'),
            'payment_cancel_message' => (string) StoreSetting::getValue('payment_cancel_message', 'You have not been charged.'),
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
                                            TextInput::make('billing_suspend_after_days')
                                                ->label('Suspend After Unpaid Days')
                                                ->numeric()
                                                ->minValue(0)
                                                ->default(2)
                                                ->helperText('How many days after bill due date a linked server is suspended for non-payment. 0 = suspend on due date.'),
                                    ]),
                            ]),

                        Tab::make('Storefront')
                            ->id('storefront')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                Section::make('Hero Section')
                                    ->description('Main hero banner on the store landing page.')
                                    ->schema([
                                        TextInput::make('hero_title')
                                            ->label('Title')
                                            ->placeholder('Game Servers'),
                                        Textarea::make('hero_subtitle')
                                            ->label('Subtitle')
                                            ->placeholder('200+ supported games on Ryzen 9 9950X3D...')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                        TextInput::make('hero_cta_text')
                                            ->label('Primary Button Text')
                                            ->placeholder('Browse Catalog'),
                                        TextInput::make('hero_cta2_text')
                                            ->label('Secondary Button Text')
                                            ->placeholder('Join Discord'),
                                    ])->columns(2),

                                Section::make('Hero Stat Cards')
                                    ->description('Customize the four stat cards shown in the store hero section.')
                                    ->schema([
                                        TextInput::make('hero_stat1_value')
                                            ->label('Stat 1 — Value')
                                            ->placeholder('$20'),
                                        TextInput::make('hero_stat1_label')
                                            ->label('Stat 1 — Label')
                                            ->placeholder('Starting monthly'),
                                        TextInput::make('hero_stat2_value')
                                            ->label('Stat 2 — Value')
                                            ->placeholder('200+'),
                                        TextInput::make('hero_stat2_label')
                                            ->label('Stat 2 — Label')
                                            ->placeholder('Games supported'),
                                        TextInput::make('hero_stat3_value')
                                            ->label('Stat 3 — Value')
                                            ->placeholder('9950X3D'),
                                        TextInput::make('hero_stat3_label')
                                            ->label('Stat 3 — Label')
                                            ->placeholder('Ryzen CPU'),
                                        TextInput::make('hero_stat4_value')
                                            ->label('Stat 4 — Value')
                                            ->placeholder('NVMe'),
                                        TextInput::make('hero_stat4_label')
                                            ->label('Stat 4 — Label')
                                            ->placeholder('Fast storage'),
                                    ])->columns(2),

                                Section::make('Featured Products Section')
                                    ->schema([
                                        TextInput::make('featured_heading')
                                            ->label('Heading')
                                            ->placeholder('Featured'),
                                        TextInput::make('featured_subtitle')
                                            ->label('Subtitle')
                                            ->placeholder('Hand-picked configurations for a quick start.'),
                                    ])->columns(2),

                                Section::make('Catalog Section')
                                    ->schema([
                                        TextInput::make('catalog_heading')
                                            ->label('Heading')
                                            ->placeholder('All Configurations'),
                                    ]),

                                Section::make('Why Us Feature Cards')
                                    ->description('Six feature cards shown at the bottom of the store page.')
                                    ->schema([
                                        TextInput::make('features_heading')
                                            ->label('Section Heading')
                                            ->placeholder('Why Shadow Haven')
                                            ->columnSpanFull(),
                                        TextInput::make('feature1_title')
                                            ->label('Feature 1 — Title')
                                            ->placeholder('Fast Launch'),
                                        TextInput::make('feature1_desc')
                                            ->label('Feature 1 — Description')
                                            ->placeholder('Provisioning and checkout wired to minimize...'),
                                        TextInput::make('feature2_title')
                                            ->label('Feature 2 — Title')
                                            ->placeholder('Clean Pricing'),
                                        TextInput::make('feature2_desc')
                                            ->label('Feature 2 — Description')
                                            ->placeholder('Straightforward catalog, visible monthly costs...'),
                                        TextInput::make('feature3_title')
                                            ->label('Feature 3 — Title')
                                            ->placeholder('200+ Games'),
                                        TextInput::make('feature3_desc')
                                            ->label('Feature 3 — Description')
                                            ->placeholder('A large supported game list with breadth...'),
                                        TextInput::make('feature4_title')
                                            ->label('Feature 4 — Title')
                                            ->placeholder('Real Hardware'),
                                        TextInput::make('feature4_desc')
                                            ->label('Feature 4 — Description')
                                            ->placeholder('High-cache Ryzen 9 9950X3D CPU and NVMe...'),
                                        TextInput::make('feature5_title')
                                            ->label('Feature 5 — Title')
                                            ->placeholder('DDoS Protected'),
                                        TextInput::make('feature5_desc')
                                            ->label('Feature 5 — Description')
                                            ->placeholder('DDoS mitigation and hardened infrastructure...'),
                                        TextInput::make('feature6_title')
                                            ->label('Feature 6 — Title')
                                            ->placeholder('Unified Management'),
                                        TextInput::make('feature6_desc')
                                            ->label('Feature 6 — Description')
                                            ->placeholder('Billing, payments, and server management...'),
                                    ])->columns(2),

                                Section::make('Product Detail Page')
                                    ->schema([
                                        TextInput::make('product_tagline')
                                            ->label('Tagline')
                                            ->placeholder('Instant deployment · DDoS protected · Ryzen 9 9950X3D'),
                                        TextInput::make('product_cta_text')
                                            ->label('CTA Button Text')
                                            ->placeholder('Configure & Deploy'),
                                    ])->columns(2),

                                Section::make('Checkout Page')
                                    ->schema([
                                        TextInput::make('checkout_secure_text')
                                            ->label('Secure Payment Text')
                                            ->placeholder('SSL Encrypted · Secure Payments'),
                                    ]),

                                Section::make('Payment Pages')
                                    ->schema([
                                        TextInput::make('payment_success_title')
                                            ->label('Success Page Title')
                                            ->placeholder('Payment Successful'),
                                        TextInput::make('payment_success_message')
                                            ->label('Success Message')
                                            ->placeholder('Your server will be provisioned shortly.'),
                                        TextInput::make('payment_cancel_title')
                                            ->label('Cancel Page Title')
                                            ->placeholder('Payment Cancelled'),
                                        TextInput::make('payment_cancel_message')
                                            ->label('Cancel Message')
                                            ->placeholder('You have not been charged.'),
                                    ])->columns(2),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $this->authorize('update storeSetting');

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
        StoreSetting::setValue('billing_suspend_after_days', (string) max(0, (int) ($data['billing_suspend_after_days'] ?? 2)));

        // Branding
        $textKeys = [
            'hero_title', 'hero_subtitle', 'hero_cta_text', 'hero_cta2_text',
            'featured_heading', 'featured_subtitle',
            'catalog_heading',
            'features_heading',
            'product_tagline', 'product_cta_text',
            'checkout_secure_text',
            'payment_success_title', 'payment_success_message',
            'payment_cancel_title', 'payment_cancel_message',
        ];

        foreach ($textKeys as $key) {
            StoreSetting::setValue($key, trim((string) ($data[$key] ?? '')));
        }

        // Hero Stats
        foreach (range(1, 4) as $i) {
            StoreSetting::setValue("hero_stat{$i}_value", trim((string) ($data["hero_stat{$i}_value"] ?? '')));
            StoreSetting::setValue("hero_stat{$i}_label", trim((string) ($data["hero_stat{$i}_label"] ?? '')));
        }

        // Feature Cards
        foreach (range(1, 6) as $i) {
            StoreSetting::setValue("feature{$i}_title", trim((string) ($data["feature{$i}_title"] ?? '')));
            StoreSetting::setValue("feature{$i}_desc", trim((string) ($data["feature{$i}_desc"] ?? '')));
        }

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
