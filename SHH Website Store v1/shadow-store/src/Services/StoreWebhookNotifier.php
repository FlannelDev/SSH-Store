<?php

namespace App\Plugins\ShadowStore\Services;

use App\Plugins\ShadowStore\Models\Order;
use App\Plugins\ShadowStore\Models\PaymentReceipt;
use App\Plugins\ShadowStore\Models\ServerBilling;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class StoreWebhookNotifier
{
    public function sendOrderCreated(Order $order): void
    {
        if (!$this->shouldSend()) {
            return;
        }

        $externalId = 'store_webhook:order_created:' . $order->id;

        if ($this->hasDeliveryMarker($externalId)) {
            return;
        }

        $payload = [
            'order_id' => $order->id,
        ];

        if ($this->dispatch(
            event: 'store_order_created',
            title: 'New Store Order',
            description: 'A new store order was created and is awaiting payment.',
            fields: [
                ['name' => 'Order', 'value' => $order->order_number, 'inline' => true],
                ['name' => 'Status', 'value' => strtoupper((string) $order->status), 'inline' => true],
                ['name' => 'Customer', 'value' => $order->user?->username ?? ('User #' . $order->user_id), 'inline' => true],
                ['name' => 'Product', 'value' => $order->product?->name ?? ('Product #' . $order->product_id), 'inline' => false],
                ['name' => 'Plan', 'value' => $order->tier_label ?: ('Slots: ' . ($order->slots ?? 0)), 'inline' => true],
                ['name' => 'Total', 'value' => '$' . number_format((float) $order->total, 2) . ' ' . strtoupper((string) $order->currency), 'inline' => true],
                ['name' => 'Payment Method', 'value' => ucfirst((string) $order->payment_method), 'inline' => true],
            ],
            color: 16098851,
        )) {
            $this->storeDeliveryMarker($externalId, 'order_created', $payload);
        }
    }

    public function sendOrderPaid(Order $order, string $reference, string $eventLabel = 'payment_received'): void
    {
        if (!$this->shouldSend()) {
            return;
        }

        $externalId = 'store_webhook:order_paid:' . $order->id . ':' . $reference;

        if ($this->hasDeliveryMarker($externalId)) {
            return;
        }

        $payload = [
            'order_id' => $order->id,
            'reference' => $reference,
        ];

        if ($this->dispatch(
            event: 'store_order_paid',
            title: 'Store Payment Received',
            description: 'A store order payment was confirmed successfully.',
            fields: [
                ['name' => 'Order', 'value' => $order->order_number, 'inline' => true],
                ['name' => 'Customer', 'value' => $order->user?->username ?? ('User #' . $order->user_id), 'inline' => true],
                ['name' => 'Status', 'value' => strtoupper((string) $order->status), 'inline' => true],
                ['name' => 'Product', 'value' => $order->product?->name ?? ('Product #' . $order->product_id), 'inline' => false],
                ['name' => 'Amount', 'value' => '$' . number_format((float) $order->total, 2) . ' ' . strtoupper((string) $order->currency), 'inline' => true],
                ['name' => 'Reference', 'value' => $reference, 'inline' => true],
                ['name' => 'Payment Method', 'value' => ucfirst((string) ($order->payment_method ?: 'unknown')), 'inline' => true],
            ],
            color: 5763719,
        )) {
            $this->storeDeliveryMarker($externalId, $eventLabel, $payload);
        }
    }

    public function sendServerBillingPaid(ServerBilling $billing, string $reference, string $eventLabel = 'server_billing_paid'): void
    {
        if (!$this->shouldSend()) {
            return;
        }

        $externalId = 'store_webhook:server_billing_paid:' . $billing->id . ':' . $reference;

        if ($this->hasDeliveryMarker($externalId)) {
            return;
        }

        $payload = [
            'billing_id' => $billing->id,
            'reference' => $reference,
        ];

        $amount = filled($billing->billing_amount) && (float) $billing->billing_amount > 0
            ? (float) $billing->billing_amount
            : (float) ($billing->node_amount ?? 0);

        if ($this->dispatch(
            event: 'store_server_billing_paid',
            title: 'Server Billing Payment Received',
            description: 'A server billing invoice was paid successfully.',
            fields: [
                ['name' => 'Server', 'value' => $billing->server?->name ?? ('Server #' . $billing->server_id), 'inline' => true],
                ['name' => 'Customer', 'value' => $billing->user?->username ?? ('User #' . $billing->user_id), 'inline' => true],
                ['name' => 'Billing ID', 'value' => (string) $billing->id, 'inline' => true],
                ['name' => 'Amount', 'value' => '$' . number_format($amount, 2) . ' ' . strtoupper((string) config('shadow-store.currency', 'USD')), 'inline' => true],
                ['name' => 'Reference', 'value' => $reference, 'inline' => true],
            ],
            color: 5763719,
        )) {
            $this->storeDeliveryMarker($externalId, $eventLabel, $payload);
        }
    }

    protected function shouldSend(): bool
    {
        return $this->webhookEnabled() && $this->webhookUrl() !== '';
    }

    protected function dispatch(string $event, string $title, string $description, array $fields, int $color): bool
    {
        $url = $this->webhookUrl();
        $mention = trim($this->webhookMention());

        try {
            if ($this->isDiscordWebhook($url)) {
                $payload = [
                    'content' => $mention !== '' ? $mention : null,
                    'embeds' => [[
                        'title' => $title,
                        'description' => $description,
                        'color' => $color,
                        'fields' => $fields,
                        'footer' => ['text' => 'Shadow Store'],
                        'timestamp' => now()->toIso8601String(),
                    ]],
                ];

                $allowedMentions = $this->discordAllowedMentions($mention);
                if ($allowedMentions !== null) {
                    $payload['allowed_mentions'] = $allowedMentions;
                }

                Http::timeout(8)->asJson()->post($url, $payload)->throw();
                return true;
            }

            Http::timeout(8)->asJson()->post($url, [
                'event' => $event,
                'message' => $mention,
                'title' => $title,
                'description' => $description,
                'fields' => $fields,
                'sent_at' => now()->toIso8601String(),
            ])->throw();

            return true;
        } catch (Throwable $exception) {
            Log::warning('Shadow Store webhook delivery failed.', [
                'event' => $event,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    protected function hasDeliveryMarker(string $externalId): bool
    {
        return PaymentReceipt::query()
            ->where('external_id', $externalId)
            ->exists();
    }

    protected function storeDeliveryMarker(string $externalId, string $type, array $payload = []): bool
    {
        try {
            PaymentReceipt::create([
                'provider' => 'store_webhook',
                'external_id' => $externalId,
                'type' => $type,
                'payload' => $payload,
                'processed_at' => now(),
            ]);

            return true;
        } catch (QueryException) {
            return false;
        }
    }

    protected function webhookEnabled(): bool
    {
        return (bool) (config('shadow-store.webhooks.enabled') ?: config('server-notes.webhook_enabled', false));
    }

    protected function webhookUrl(): string
    {
        return (string) (config('shadow-store.webhooks.url') ?: config('server-notes.webhook_url', ''));
    }

    protected function webhookMention(): string
    {
        return (string) (config('shadow-store.webhooks.mention') ?: config('server-notes.webhook_mention', ''));
    }

    protected function isDiscordWebhook(string $url): bool
    {
        return str_contains($url, 'discord.com/api/webhooks/') || str_contains($url, 'discordapp.com/api/webhooks/');
    }

    protected function discordAllowedMentions(string $mention): ?array
    {
        if ($mention === '') {
            return null;
        }

        preg_match_all('/<@&(\d+)>/', $mention, $roleMatches);
        preg_match_all('/<@!?(\d+)>/', $mention, $userMatches);

        $roles = array_values(array_unique($roleMatches[1] ?? []));
        $users = array_values(array_unique($userMatches[1] ?? []));

        if (str_contains($mention, '@everyone') || str_contains($mention, '@here')) {
            $payload = ['parse' => ['everyone']];
            if ($roles !== []) {
                $payload['roles'] = $roles;
            }
            if ($users !== []) {
                $payload['users'] = $users;
            }
            return $payload;
        }

        if ($roles !== [] || $users !== []) {
            $payload = ['parse' => []];
            if ($roles !== []) {
                $payload['roles'] = $roles;
            }
            if ($users !== []) {
                $payload['users'] = $users;
            }
            return $payload;
        }

        return ['parse' => []];
    }
}