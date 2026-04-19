<?php

return [
    'stripe' => [
        'key' => env('SHH_STRIPE_KEY', ''),
        'secret' => env('SHH_STRIPE_SECRET', ''),
        'webhook_secret' => env('SHH_STRIPE_WEBHOOK_SECRET', ''),
    ],

    'paypal' => [
        'client_id' => env('SHH_PAYPAL_CLIENT_ID', ''),
        'client_secret' => env('SHH_PAYPAL_CLIENT_SECRET', ''),
        'mode' => env('SHH_PAYPAL_MODE', 'sandbox'),
    ],

    'billing' => [
        'suspend_after_days' => (int) env('SHH_BILLING_SUSPEND_AFTER_DAYS', 2),
    ],
];
