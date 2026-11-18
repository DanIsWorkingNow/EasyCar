<?php

/*
|--------------------------------------------------------------------------
| Payments
|--------------------------------------------------------------------------
|
| Driver-based payment framework (app/Payments). Every gateway implements
| App\Payments\Contracts\PaymentGateway and is resolved through
| App\Payments\PaymentManager, so adding a new provider is one class plus
| an entry below — controllers, webhooks, refunds and the admin UI pick it
| up automatically.
|
| A gateway is offered to customers only when it is `enabled` AND its
| credentials are present (PaymentGateway::isConfigured()).
|
*/

return [

    'enabled' => env('PAYMENTS_ENABLED', true),

    'currency' => env('PAYMENTS_CURRENCY', 'MYR'),

    'default' => env('PAYMENTS_DEFAULT_GATEWAY', 'sandbox'),

    // Unpaid pending bookings are released after this many minutes so an
    // abandoned checkout doesn't hold a car indefinitely (see the
    // bookings:expire-unpaid scheduled command). 0 disables expiry.
    'hold_minutes' => (int) env('PAYMENTS_HOLD_MINUTES', 60),

    'gateways' => [

        // Stripe Checkout — cards, FPX, GrabPay. Supports API refunds.
        'stripe' => [
            'enabled' => env('STRIPE_ENABLED', false),
            'label' => 'Card / FPX / GrabPay',
            'description' => 'Visa, Mastercard, Amex, FPX online banking and GrabPay via Stripe.',
            'secret' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'base_url' => env('STRIPE_BASE_URL', 'https://api.stripe.com/v1'),
            'webhook_tolerance' => 300,
        ],

        // Billplz — Malaysian FPX online banking.
        'billplz' => [
            'enabled' => env('BILLPLZ_ENABLED', false),
            'label' => 'FPX Online Banking (Billplz)',
            'description' => 'Pay directly from any Malaysian bank account.',
            'api_key' => env('BILLPLZ_API_KEY'),
            'collection_id' => env('BILLPLZ_COLLECTION_ID'),
            'x_signature' => env('BILLPLZ_X_SIGNATURE'),
            'sandbox' => env('BILLPLZ_SANDBOX', true),
        ],

        // ToyyibPay — Malaysian FPX + cards.
        'toyyibpay' => [
            'enabled' => env('TOYYIBPAY_ENABLED', false),
            'label' => 'FPX / Card (ToyyibPay)',
            'description' => 'Malaysian online banking and debit/credit cards.',
            'secret_key' => env('TOYYIBPAY_SECRET_KEY'),
            'category_code' => env('TOYYIBPAY_CATEGORY_CODE'),
            'sandbox' => env('TOYYIBPAY_SANDBOX', true),
        ],

        // Built-in simulated gateway for local development, demos and
        // automated tests. Never enable it in production.
        'sandbox' => [
            'enabled' => env('PAYMENTS_SANDBOX_ENABLED', env('APP_ENV', 'production') !== 'production'),
            'label' => 'Sandbox (test payments)',
            'description' => 'Simulated checkout for testing — no real money moves.',
        ],
    ],
];
