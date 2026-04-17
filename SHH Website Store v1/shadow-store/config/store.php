<?php

return array (
  'stripe' => 
  array (
    'enabled' => env('SHADOW_STORE_STRIPE_ENABLED', false),
    'key' => env('SHADOW_STORE_STRIPE_KEY', ''),
    'secret' => env('SHADOW_STORE_STRIPE_SECRET', ''),
    'webhook_secret' => env('SHADOW_STORE_STRIPE_WEBHOOK_SECRET', ''),
  ),
  'paypal' => 
  array (
    'enabled' => env('SHADOW_STORE_PAYPAL_ENABLED', false),
    'client_id' => env('SHADOW_STORE_PAYPAL_CLIENT_ID', ''),
    'client_secret' => env('SHADOW_STORE_PAYPAL_CLIENT_SECRET', ''),
    'sandbox' => env('SHADOW_STORE_PAYPAL_SANDBOX', true),
  ),
  'tax_rate' => env('SHADOW_STORE_TAX_RATE', '7.2'),
  'currency' => env('SHADOW_STORE_CURRENCY', 'USD'),
  'webhooks' => 
  array (
    'enabled' => env('SHADOW_STORE_WEBHOOK_ENABLED', false),
    'url' => env('SHADOW_STORE_WEBHOOK_URL', ''),
    'mention' => env('SHADOW_STORE_WEBHOOK_MENTION', ''),
  ),
  'billing_notifications' => 
  array (
    'templates' => 
    array (
      'due' => 
      array (
        'subject' => 'Payment Due: {server_name}',
        'body' => 'Hello {client_name},

This is a reminder that payment for {server_name} is due on {due_date}.

If you have already paid, you can ignore this message.

You can review your services here: {panel_url}',
      ),
      'past_due' => 
      array (
        'subject' => 'Past Due Notice: {server_name}',
        'body' => 'Hello {client_name},

Your billing for {server_name} became past due on {due_date}.

If payment is not resolved within 2 days of the due date, the server will be automatically suspended.

Manage your services here: {panel_url}',
      ),
      'suspended' => 
      array (
        'subject' => 'Server Suspended: {server_name}',
        'body' => 'Hello {client_name},

Your server {server_name} has been suspended for non-payment.

The recorded due date was {due_date}.

Please log in to your panel to resolve billing: {panel_url}',
      ),
    ),
  ),
);
