<?php

declare(strict_types=1);

use MrThito\LaravelStripeConnect\Http\Middleware\EnsurePayable;
use MrThito\LaravelStripeConnect\Models\StripeConnectAccount;

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe API credentials
    |--------------------------------------------------------------------------
    */
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Package routes
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'enabled' => env('STRIPE_CONNECT_ROUTES_ENABLED', true),

        'middleware' => [
            'web',
            'auth',
            EnsurePayable::class,
            'throttle:stripe-connect',
        ],

        'account' => [
            'refresh' => 'stripe-connect.refresh',
            'return' => 'stripe-connect.return',
            'complete' => 'home',
            'connect' => 'home',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Polymorphic Stripe Connect account storage
    |--------------------------------------------------------------------------
    |
    | Publish the migration before migrating (see readme / CUSTOMIZATION.md):
    |   php artisan vendor:publish --tag=stripe-connect-migrations
    |
    | Extend StripeConnectAccount in your app and set account.model when you
    | need custom casts, accessors, or extra columns on the morph table.
    |
    */
    'account' => [
        'table' => env('STRIPE_CONNECT_ACCOUNT_TABLE', 'stripe_connect_accounts'),
        'model' => env('STRIPE_CONNECT_ACCOUNT_MODEL', StripeConnectAccount::class),
        'morph_name' => env('STRIPE_CONNECT_MORPH_NAME', 'connectable'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'security' => [

        'allowed_complete_routes' => [
            'home',
            'dashboard',
        ],

        'allowed_account_types' => [
            'express',
            'standard',
            'custom',
        ],

        'allowed_create_account_keys' => [
            'type',
            'country',
            'email',
            'capabilities',
            'business_type',
            'business_profile',
            'company',
            'individual',
            'metadata',
            'settings',
            'tos_acceptance',
        ],

        'allowed_capabilities' => [
            'transfers',
            'card_payments',
            'tax_reporting_us_1099_k',
            'tax_reporting_us_1099_misc',
        ],

        'max_transfer_amount' => env('STRIPE_CONNECT_MAX_TRANSFER_AMOUNT'),

        'rate_limit_per_minute' => (int) env('STRIPE_CONNECT_RATE_LIMIT', 10),
    ],
];
