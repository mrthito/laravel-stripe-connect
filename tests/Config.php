<?php

declare(strict_types=1);

use MrThito\LaravelStripeConnect\Models\StripeConnectAccount;

it('exposes default stripe connect configuration', function () {
    expect(config('stripe_connect.stripe.secret'))->toBe('sk_test_example123456')
        ->and(config('stripe_connect.account.table'))->toBe('stripe_connect_accounts')
        ->and(config('stripe_connect.account.model'))->toBe(StripeConnectAccount::class)
        ->and(config('stripe_connect.routes.enabled'))->toBeTrue()
        ->and(config('stripe_connect.routes.middleware'))->toContain('throttle:stripe-connect')
        ->and(config('stripe_connect.security.allowed_account_types'))->toContain('express')
        ->and(config('stripe_connect.security.rate_limit_per_minute'))->toBe(60);
});

it('allows a custom stripe connect account model', function () {
    config(['stripe_connect.account.model' => StripeConnectAccount::class]);

    expect(config('stripe_connect.account.model'))->toBe(StripeConnectAccount::class);
});
