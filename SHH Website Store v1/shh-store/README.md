# SHH Store — Pelican Panel Plugin

Game server storefront plugin for [Pelican Panel](https://pelican.dev) with checkout, payments, order management, coupon support, and unpaid-billing suspension controls.

## Features

- Product catalog with categories and billing cycle pricing (monthly/quarterly/annually)
- Stripe + PayPal checkout integration
- Coupon management (`min_order`, usage limits, first-month-only support)
- Filament admin resources for:
	- Clients
	- Categories
	- Products
	- Orders
	- Coupons
	- Store settings
- Order-to-server/node linking from admin
- Unpaid billing suspension workflow:
	- Configurable suspension delay in days
	- Manual suspend/unsuspend actions
	- Batch processing command for overdue unpaid linked servers
	- Linked server visibility from client profiles

## Installation

1. Place this plugin as `plugins/shh-store/` in your Pelican installation.
2. Ensure environment values are set in `.env`:

```env
SHH_STRIPE_KEY=pk_test_...
SHH_STRIPE_SECRET=sk_test_...
SHH_STRIPE_WEBHOOK_SECRET=whsec_...
SHH_PAYPAL_CLIENT_ID=...
SHH_PAYPAL_CLIENT_SECRET=...
SHH_PAYPAL_MODE=sandbox
SHH_BILLING_SUSPEND_AFTER_DAYS=2
```

3. Run migrations:

```bash
php artisan migrate
```

4. (Optional) seed sample catalog data:

```bash
php artisan db:seed --class="Database\Seeders\SHHStoreSeeder"
```

## Routes

> Current plugin routes are under `/storestaging`.

| Route | Path | Description |
|-------|------|-------------|
| `shh-store.store` | `/storestaging` | Storefront catalog |
| `shh-store.product` | `/storestaging/product/{slug}` | Product detail |
| `shh-store.checkout` | `/storestaging/checkout/{slug}/{cycle?}` | Checkout |
| `shh-store.payment.success` | `/storestaging/payment/success/{order}` | Payment success |
| `shh-store.payment.cancel` | `/storestaging/payment/cancel/{order}` | Payment cancelled |
| `shh-store.paypal.capture` | `/storestaging/payment/paypal/capture/{order}` | PayPal capture callback |
| `shh-store.webhooks.stripe` | `/webhooks/shh-store/stripe` | Stripe webhook endpoint |

## Unpaid Suspension Workflow

- Configure delay in admin: **Store → Settings → General → Suspend After Unpaid Days**
- Link a `Server` and optional `Node` to an order in **Store → Orders**
- Process overdue linked orders manually from **Store → Orders → Process Unpaid Suspensions**
- CLI equivalent:

```bash
php artisan shh-store:process-unpaid-suspensions
```

- Client-level visibility/actions:
	- **Store → Clients → View → Linked Servers**
	- Includes overdue status + eligibility badge, suspend/unsuspend actions, and order shortcut

## Configuration

Primary config lives in `config/shh-store.php` and supports:

- Stripe keys/webhook secret
- PayPal client credentials/mode
- Billing suspension default (`billing.suspend_after_days`)

Most payment values are managed in **Store → Settings** and persisted to `shh_store_settings`.

## Versioning Workflow

After each plugin code change, bump version metadata so `plugin.json` and `update.json` stay in sync.

```bash
php artisan shh-store:bump-version patch
```

Optional bump types:

```bash
php artisan shh-store:bump-version minor
php artisan shh-store:bump-version major
```

This command updates both:

- `shh-store/plugin.json` (`version`)
- `shh-store/update.json` (`*.version`)

## Requirements

- Pelican Panel (current compatible build)
- PHP 8.2+
- `stripe/stripe-php`
- `srmklive/paypal`

## License

MIT
