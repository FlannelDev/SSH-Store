<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Pages;

use App\Plugins\ShadowStore\Models\MediaAsset;
use App\Plugins\ShadowStore\Models\StoreSetting;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;

class StoreSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?int $navigationSort = 10;
    protected string $view = 'shadow-store::admin.pages.store-settings';

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
        return 'Store Settings';
    }

    public function mount(): void
    {
        $stripeEnabled = $this->boolSetting('stripe_enabled', config('shadow-store.stripe.enabled', false));
        $paypalEnabled = $this->boolSetting('paypal_enabled', config('shadow-store.paypal.enabled', false));
        $paypalSandbox = $this->boolSetting('paypal_sandbox', config('shadow-store.paypal.sandbox', true));

        $this->data = [
            // Stripe
            'stripe_enabled' => $stripeEnabled,
            'stripe_key' => (string) StoreSetting::getValue('stripe_key', (string) config('shadow-store.stripe.key', '')),
            'stripe_secret' => '',
            'stripe_webhook_secret' => '',
            'has_stripe_secret' => (string) StoreSetting::getValue('stripe_secret', '') !== '',
            'has_stripe_webhook_secret' => (string) StoreSetting::getValue('stripe_webhook_secret', '') !== '',
            
            // PayPal
            'paypal_enabled' => $paypalEnabled,
            'paypal_client_id' => (string) StoreSetting::getValue('paypal_client_id', (string) config('shadow-store.paypal.client_id', '')),
            'paypal_client_secret' => '',
            'paypal_sandbox' => $paypalSandbox,
            'has_paypal_client_secret' => (string) StoreSetting::getValue('paypal_client_secret', '') !== '',
            
            // General
            'tax_rate' => (string) StoreSetting::getValue('tax_rate', (string) config('shadow-store.tax_rate', 0)),
            'currency' => (string) StoreSetting::getValue('currency', (string) config('shadow-store.currency', 'USD')),
            'webhook_enabled' => $this->boolSetting('webhook_enabled', config('shadow-store.webhooks.enabled', false)),
            'webhook_url' => (string) StoreSetting::getValue('webhook_url', (string) config('shadow-store.webhooks.url', '')),
            'webhook_mention' => (string) StoreSetting::getValue('webhook_mention', (string) config('shadow-store.webhooks.mention', '')),

            // Billing automation
            'billing_due_subject' => (string) StoreSetting::getValue('billing_due_subject', (string) config('shadow-store.billing_notifications.templates.due.subject', '')),
            'billing_due_body' => (string) StoreSetting::getValue('billing_due_body', (string) config('shadow-store.billing_notifications.templates.due.body', '')),
            'billing_past_due_subject' => (string) StoreSetting::getValue('billing_past_due_subject', (string) config('shadow-store.billing_notifications.templates.past_due.subject', '')),
            'billing_past_due_body' => (string) StoreSetting::getValue('billing_past_due_body', (string) config('shadow-store.billing_notifications.templates.past_due.body', '')),
            'billing_suspended_subject' => (string) StoreSetting::getValue('billing_suspended_subject', (string) config('shadow-store.billing_notifications.templates.suspended.subject', '')),
            'billing_suspended_body' => (string) StoreSetting::getValue('billing_suspended_body', (string) config('shadow-store.billing_notifications.templates.suspended.body', '')),

            // Store header
            'header_badge_text' => (string) StoreSetting::getValue('header_badge_text', 'SH'),
            'header_logo_asset_id' => StoreSetting::getValue('header_logo_asset_id'),
            'header_logo_url' => (string) StoreSetting::getValue('header_logo_url', ''),
            'header_brand_name' => (string) StoreSetting::getValue('header_brand_name', 'Shadow Haven Hosting'),
            'header_brand_tagline' => (string) StoreSetting::getValue('header_brand_tagline', 'Game infrastructure'),
            'header_store_label' => (string) StoreSetting::getValue('header_store_label', 'Game Servers'),
            'header_store_url' => (string) StoreSetting::getValue('header_store_url', '/store'),
            'header_dedicated_label' => (string) StoreSetting::getValue('header_dedicated_label', 'Dedicated'),
            'header_dedicated_url' => (string) StoreSetting::getValue('header_dedicated_url', '/store/dedicated'),
            'header_msa_label' => (string) StoreSetting::getValue('header_msa_label', 'MSA'),
            'header_msa_url' => (string) StoreSetting::getValue('header_msa_url', '/store/msa'),
            'header_wiki_label' => (string) StoreSetting::getValue('header_wiki_label', 'Wiki'),
            'header_wiki_url' => (string) StoreSetting::getValue('header_wiki_url', '/wiki'),
            'header_discord_label' => (string) StoreSetting::getValue('header_discord_label', 'Discord'),
            'header_discord_url' => (string) StoreSetting::getValue('header_discord_url', 'https://discord.gg/AqCVPtpgYQ'),

            // Store content
            'home_kicker' => (string) StoreSetting::getValue('home_kicker', 'Built for communities that hate lag'),
            'home_title' => (string) StoreSetting::getValue('home_title', 'Game servers with dedicated-grade headroom and zero bargain-bin packaging.'),
            'home_subtitle' => (string) StoreSetting::getValue('home_subtitle', 'Launch Arma Reforger, survival worlds, and performance-heavy stacks across a catalog of 200+ supported games on Ryzen 9 9950X3D infrastructure with sensible density, fast storage, and clean billing.'),
            'home_primary_cta_label' => (string) StoreSetting::getValue('home_primary_cta_label', 'Browse Game Servers'),
            'home_primary_cta_url' => (string) StoreSetting::getValue('home_primary_cta_url', '#catalog'),
            'home_secondary_cta_label' => (string) StoreSetting::getValue('home_secondary_cta_label', 'Explore Dedicated Machines'),
            'home_secondary_cta_url' => (string) StoreSetting::getValue('home_secondary_cta_url', '/store/dedicated'),
            'home_media_asset_id' => StoreSetting::getValue('home_media_asset_id'),
            'home_content' => (string) StoreSetting::getValue('home_content', ''),
            'dedicated_title' => (string) StoreSetting::getValue('dedicated_title', 'Dedicated Servers'),
            'dedicated_subtitle' => (string) StoreSetting::getValue('dedicated_subtitle', 'Browse live dedicated server inventory and order directly through the embedded provisioning catalog.'),
            'dedicated_media_asset_id' => StoreSetting::getValue('dedicated_media_asset_id'),
            'dedicated_content' => (string) StoreSetting::getValue('dedicated_content', ''),
            'msa_title' => (string) StoreSetting::getValue('msa_title', 'Master Services Agreement'),
            'msa_content' => (string) StoreSetting::getValue('msa_content', ''),
            'footer_notice_title' => (string) StoreSetting::getValue('footer_notice_title', 'Servers are in partnership with Thunder Buddies Studio'),
            'footer_notice_body' => (string) StoreSetting::getValue('footer_notice_body', 'Support provided by Thunder Buddies Studio. Servers by Shadow Haven.'),
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
                                        ->helperText('Webhook endpoint: ' . url('/store/webhook/stripe') . '. Leave blank to keep current secret.'),
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
                        ->icon('heroicon-o-cog')
                        ->schema([
                            Section::make('General Settings')
                                ->schema([
                                    TextInput::make('tax_rate')
                                        ->label('Tax Rate')
                                        ->numeric()
                                        ->suffix('%')
                                        ->placeholder('0')
                                        ->helperText('Tax percentage to apply to orders (0 for no tax)'),
                                    TextInput::make('currency')
                                        ->label('Currency')
                                        ->placeholder('USD')
                                        ->helperText('3-letter currency code (e.g., USD, EUR, GBP)'),
                                ]),
                            Section::make('Webhook Notifications')
                                ->description('Notify staff when orders are created and payments are received. Discord webhooks are supported.')
                                ->schema([
                                    Toggle::make('webhook_enabled')
                                        ->label('Enable Store Webhooks'),
                                    TextInput::make('webhook_url')
                                        ->label('Webhook URL')
                                        ->placeholder('https://discord.com/api/webhooks/...')
                                        ->url(),
                                    TextInput::make('webhook_mention')
                                        ->label('Mention Text')
                                        ->placeholder('<@&ROLE_ID> or @here')
                                        ->helperText('For Discord role pings, use the raw role mention format like <@&ROLE_ID>.'),
                                ]),
                        ]),

                    Tab::make('Legal / MSA')
                        ->id('legal-msa')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make('Master Services Agreement')
                                ->id('legal-msa-section')
                                ->description('Edit the agreement page displayed to customers during checkout. Public page: ' . url('/store/msa'))
                                ->schema([
                                    TextInput::make('msa_title')
                                        ->label('Page Title'),
                                    RichEditor::make('msa_content')
                                        ->label('Agreement Content')
                                        ->fileAttachmentsDisk('public')
                                        ->fileAttachmentsDirectory('store/editor')
                                        ->fileAttachmentsVisibility('public')
                                        ->helperText('Use the built-in editor to manage the public MSA page. You can upload images here or reuse URLs from Store > Media Library.')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tab::make('Content')
                        ->id('content')
                        ->icon('heroicon-o-pencil-square')
                        ->schema([
                            Section::make('Store Header')
                                ->id('store-header')
                                ->description('Global brand and navigation used across the public store pages.')
                                ->schema([
                                    Select::make('header_logo_asset_id')
                                        ->label('Header Logo Image')
                                        ->options(fn () => MediaAsset::query()->orderBy('name')->pluck('name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->helperText('Optional shared media asset. If set, this replaces the badge text square.'),
                                    TextInput::make('header_logo_url')
                                        ->label('Header Logo URL')
                                        ->helperText('Optional fallback image URL if you are not using the shared media library.')
                                        ->url(),
                                    TextInput::make('header_badge_text')
                                        ->label('Badge Text Fallback')
                                        ->maxLength(8),
                                    TextInput::make('header_brand_name')
                                        ->label('Brand Name'),
                                    TextInput::make('header_brand_tagline')
                                        ->label('Brand Tagline'),
                                    TextInput::make('header_store_label')
                                        ->label('Primary Nav Label'),
                                    TextInput::make('header_store_url')
                                        ->label('Primary Nav URL'),
                                    TextInput::make('header_dedicated_label')
                                        ->label('Dedicated Nav Label'),
                                    TextInput::make('header_dedicated_url')
                                        ->label('Dedicated Nav URL'),
                                    TextInput::make('header_msa_label')
                                        ->label('MSA Nav Label'),
                                    TextInput::make('header_msa_url')
                                        ->label('MSA Nav URL'),
                                    TextInput::make('header_wiki_label')
                                        ->label('Wiki Nav Label'),
                                    TextInput::make('header_wiki_url')
                                        ->label('Wiki Nav URL'),
                                    TextInput::make('header_discord_label')
                                        ->label('Discord Nav Label'),
                                    TextInput::make('header_discord_url')
                                        ->label('Discord Nav URL'),
                                ]),
                            Section::make('Storefront Hero')
                                ->id('storefront-hero')
                                ->description('Manage the copy and featured content on the main /store landing page.')
                                ->schema([
                                    TextInput::make('home_kicker')
                                        ->label('Kicker'),
                                    TextInput::make('home_title')
                                        ->label('Headline'),
                                    Textarea::make('home_subtitle')
                                        ->label('Subtitle')
                                        ->rows(3),
                                    TextInput::make('home_primary_cta_label')
                                        ->label('Primary CTA Label'),
                                    TextInput::make('home_primary_cta_url')
                                        ->label('Primary CTA URL'),
                                    TextInput::make('home_secondary_cta_label')
                                        ->label('Secondary CTA Label'),
                                    TextInput::make('home_secondary_cta_url')
                                        ->label('Secondary CTA URL'),
                                    Select::make('home_media_asset_id')
                                        ->label('Hero Image')
                                        ->options(fn () => MediaAsset::query()->orderBy('name')->pluck('name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->helperText('Optional shared image from Store > Media Library.'),
                                    RichEditor::make('home_content')
                                        ->label('Additional Landing Page Content')
                                        ->fileAttachmentsDisk('public')
                                        ->fileAttachmentsDirectory('store/editor')
                                        ->fileAttachmentsVisibility('public')
                                        ->helperText('Adds an editable content block beneath the hero section on /store.')
                                        ->columnSpanFull(),
                                ]),
                            Section::make('Dedicated Page')
                                ->id('dedicated-page')
                                ->description('Manage the public /store/dedicated heading and supporting content.')
                                ->schema([
                                    TextInput::make('dedicated_title')
                                        ->label('Title'),
                                    Textarea::make('dedicated_subtitle')
                                        ->label('Subtitle')
                                        ->rows(3),
                                    Select::make('dedicated_media_asset_id')
                                        ->label('Feature Image')
                                        ->options(fn () => MediaAsset::query()->orderBy('name')->pluck('name', 'id'))
                                        ->searchable()
                                        ->preload(),
                                    RichEditor::make('dedicated_content')
                                        ->label('Additional Dedicated Page Content')
                                        ->fileAttachmentsDisk('public')
                                        ->fileAttachmentsDirectory('store/editor')
                                        ->fileAttachmentsVisibility('public')
                                        ->helperText('Displayed above the inventory widget on /store/dedicated.')
                                        ->columnSpanFull(),
                                ]),
                            Section::make('Footer Notice')
                                ->id('footer-notice')
                                ->description('Controls the highlighted notice at the bottom of the main storefront page.')
                                ->schema([
                                    TextInput::make('footer_notice_title')
                                        ->label('Notice Title'),
                                    Textarea::make('footer_notice_body')
                                        ->label('Notice Body')
                                        ->rows(2),
                                ]),
                        ]),

                    Tab::make('Billing Emails')
                        ->icon('heroicon-o-envelope')
                        ->schema([
                            Section::make('Billing Email Templates')
                                ->description('Edit the emails sent for due, past-due, and suspended billing states. Available placeholders: {client_name}, {server_name}, {product_name}, {order_number}, {due_date}, {panel_url}.')
                                ->schema([
                                    TextInput::make('billing_due_subject')
                                        ->label('Due Email Subject'),
                                    Textarea::make('billing_due_body')
                                        ->label('Due Email Body')
                                        ->rows(6),
                                    TextInput::make('billing_past_due_subject')
                                        ->label('Past Due Email Subject'),
                                    Textarea::make('billing_past_due_body')
                                        ->label('Past Due Email Body')
                                        ->rows(6),
                                    TextInput::make('billing_suspended_subject')
                                        ->label('Suspended Email Subject'),
                                    Textarea::make('billing_suspended_body')
                                        ->label('Suspended Email Body')
                                        ->rows(6),
                                ]),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
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

        StoreSetting::setValue('tax_rate', (string) ($data['tax_rate'] ?? '0'));
        StoreSetting::setValue('currency', strtoupper(trim((string) ($data['currency'] ?? 'USD'))));
        StoreSetting::setValue('webhook_enabled', !empty($data['webhook_enabled']) ? '1' : '0');
        StoreSetting::setValue('webhook_url', trim((string) ($data['webhook_url'] ?? '')));
        StoreSetting::setValue('webhook_mention', trim((string) ($data['webhook_mention'] ?? '')));

        StoreSetting::setValue('billing_due_subject', (string) ($data['billing_due_subject'] ?? ''));
        StoreSetting::setValue('billing_due_body', (string) ($data['billing_due_body'] ?? ''));
        StoreSetting::setValue('billing_past_due_subject', (string) ($data['billing_past_due_subject'] ?? ''));
        StoreSetting::setValue('billing_past_due_body', (string) ($data['billing_past_due_body'] ?? ''));
        StoreSetting::setValue('billing_suspended_subject', (string) ($data['billing_suspended_subject'] ?? ''));
        StoreSetting::setValue('billing_suspended_body', (string) ($data['billing_suspended_body'] ?? ''));

        StoreSetting::setValue('header_badge_text', (string) ($data['header_badge_text'] ?? ''));
        StoreSetting::setValue('header_logo_asset_id', $data['header_logo_asset_id'] ?? null);
        StoreSetting::setValue('header_logo_url', (string) ($data['header_logo_url'] ?? ''));
        StoreSetting::setValue('header_brand_name', (string) ($data['header_brand_name'] ?? ''));
        StoreSetting::setValue('header_brand_tagline', (string) ($data['header_brand_tagline'] ?? ''));
        StoreSetting::setValue('header_store_label', (string) ($data['header_store_label'] ?? ''));
        StoreSetting::setValue('header_store_url', (string) ($data['header_store_url'] ?? ''));
        StoreSetting::setValue('header_dedicated_label', (string) ($data['header_dedicated_label'] ?? ''));
        StoreSetting::setValue('header_dedicated_url', (string) ($data['header_dedicated_url'] ?? ''));
        StoreSetting::setValue('header_msa_label', (string) ($data['header_msa_label'] ?? ''));
        StoreSetting::setValue('header_msa_url', (string) ($data['header_msa_url'] ?? ''));
        StoreSetting::setValue('header_wiki_label', (string) ($data['header_wiki_label'] ?? ''));
        StoreSetting::setValue('header_wiki_url', (string) ($data['header_wiki_url'] ?? ''));
        StoreSetting::setValue('header_discord_label', (string) ($data['header_discord_label'] ?? ''));
        StoreSetting::setValue('header_discord_url', (string) ($data['header_discord_url'] ?? ''));

        StoreSetting::setValue('home_kicker', (string) ($data['home_kicker'] ?? ''));
        StoreSetting::setValue('home_title', (string) ($data['home_title'] ?? ''));
        StoreSetting::setValue('home_subtitle', (string) ($data['home_subtitle'] ?? ''));
        StoreSetting::setValue('home_primary_cta_label', (string) ($data['home_primary_cta_label'] ?? ''));
        StoreSetting::setValue('home_primary_cta_url', (string) ($data['home_primary_cta_url'] ?? ''));
        StoreSetting::setValue('home_secondary_cta_label', (string) ($data['home_secondary_cta_label'] ?? ''));
        StoreSetting::setValue('home_secondary_cta_url', (string) ($data['home_secondary_cta_url'] ?? ''));
        StoreSetting::setValue('home_media_asset_id', $data['home_media_asset_id'] ?? null);
        StoreSetting::setValue('home_content', (string) ($data['home_content'] ?? ''));
        StoreSetting::setValue('dedicated_title', (string) ($data['dedicated_title'] ?? ''));
        StoreSetting::setValue('dedicated_subtitle', (string) ($data['dedicated_subtitle'] ?? ''));
        StoreSetting::setValue('dedicated_media_asset_id', $data['dedicated_media_asset_id'] ?? null);
        StoreSetting::setValue('dedicated_content', (string) ($data['dedicated_content'] ?? ''));
        StoreSetting::setValue('msa_title', (string) ($data['msa_title'] ?? ''));
        StoreSetting::setValue('msa_content', (string) ($data['msa_content'] ?? ''));
        StoreSetting::setValue('footer_notice_title', (string) ($data['footer_notice_title'] ?? ''));
        StoreSetting::setValue('footer_notice_body', (string) ($data['footer_notice_body'] ?? ''));

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
