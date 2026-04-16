# SHH Store — Pelican Panel Plugin

A game server hosting storefront plugin for [Pelican Panel](https://pelican.dev). Adds a full store with product catalog, checkout, and Stripe/PayPal payment processing.

## Features

- Product catalog with categories, search, filtering, and sorting
- Featured products and billing cycle options (monthly/quarterly/annual)
- Stripe and PayPal payment integration
- Order management via Filament admin panel
- Dark-themed storefront UI with Tailwind CSS
- Livewire-powered dynamic pages

## Installation

1. Copy the `shh-store/` folder into your Pelican Panel's `plugins/` directory.

2. Add the required environment variables to your `.env`:

```env
SHH_STRIPE_KEY=pk_test_...
SHH_STRIPE_SECRET=sk_test_...
SHH_STRIPE_WEBHOOK_SECRET=whsec_...
SHH_PAYPAL_CLIENT_ID=...
SHH_PAYPAL_CLIENT_SECRET=...
SHH_PAYPAL_MODE=sandbox
```

3. Run migrations:

```bash
php artisan migrate
```

4. (Optional) Seed sample products:

```bash
php artisan db:seed --class="ShhStore\Database\Seeders\ShhStoreSeeder"
```

## Routes

| Route | Path | Description |
|-------|------|-------------|
| `shh-store.store` | `/store` | Main storefront catalog |
| `shh-store.product` | `/store/{slug}` | Product detail page |
| `shh-store.checkout` | `/store/checkout/{slug}` | Checkout page |
| `shh-store.payment.success` | `/store/payment/{order}/success` | Payment success |
| `shh-store.payment.cancel` | `/store/payment/{order}/cancel` | Payment cancelled |
| `shh-store.payment.paypal.capture` | `/store/payment/{order}/paypal/capture` | PayPal capture callback |
| `shh-store.webhook.stripe` | `/webhooks/shh-store/stripe` | Stripe webhook endpoint |

## Admin Panel

The plugin adds a **Store** navigation group in the Filament admin panel with:

- **Categories** — Manage product categories
- **Products** — Manage server configurations and pricing
- **Orders** — View and manage customer orders

## Configuration

Config is published to `config/shh-store.php`. All values are read from environment variables with `SHH_` prefix.

## Requirements

- Pelican Panel (latest)
- PHP 8.2+
- `stripe/stripe-php` (auto-installed via plugin.json)
- `srmklive/paypal` (auto-installed via plugin.json)

## License

MIT
