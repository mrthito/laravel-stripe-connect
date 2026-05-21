<?php

declare(strict_types=1);

use MrThito\LaravelStripeConnect\Contracts\StripeConnect;
use MrThito\LaravelStripeConnect\Exceptions\InvalidStripeConfigurationException;
use Stripe\StripeClient;

it('registers the stripe connect contract', function () {
    $client = $this->app->make(StripeConnect::class);

    expect($client)->toBeInstanceOf(StripeClient::class);
});

it('merges package configuration', function () {
    expect(config('stripe_connect.stripe.secret'))->toBe('sk_test_example123456')
        ->and(config('stripe_connect.account.table'))->toBe('stripe_connect_accounts')
        ->and(config('stripe_connect.routes.account.refresh'))->toBe('stripe-connect.refresh')
        ->and(config('stripe_connect.security.allowed_account_types'))->toContain('express');
});

it('registers stripe connect routes', function () {
    expect($this->app['router']->has('stripe-connect.return'))->toBeTrue()
        ->and($this->app['router']->has('stripe-connect.refresh'))->toBeTrue();
});

it('ships only a publishable migration stub', function () {
    $stub = __DIR__.'/../migrations/0001_01_01_000000_create_stripe_connect_accounts_table.php.stub';
    $autoLoaded = __DIR__.'/../migrations/0001_01_01_000000_create_stripe_connect_accounts_table.php';

    expect(file_exists($stub))->toBeTrue()
        ->and(file_exists($autoLoaded))->toBeFalse();
});

it('exposes morph name configuration', function () {
    expect(config('stripe_connect.account.morph_name'))->toBe('connectable');
});

it('rejects invalid stripe secret keys', function () {
    config(['stripe_connect.stripe.secret' => 'invalid']);

    $this->app->make(StripeConnect::class);
})->throws(InvalidStripeConfigurationException::class);
