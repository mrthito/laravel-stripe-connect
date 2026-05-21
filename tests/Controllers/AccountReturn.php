<?php

declare(strict_types=1);

use MrThito\LaravelStripeConnect\Contracts\StripeConnect;
use MrThito\LaravelStripeConnect\Tests\Fixtures\PayableUser;
use Stripe\Account;
use Stripe\Exception\InvalidRequestException;

beforeEach(function () {
    $this->bindFakeStripe();
    $this->registerApplicationRoutes();
});

it('syncs stripe account status and redirects to the configured complete route', function () {
    $user = $this->makePayableUser();
    $this->fakeStripe->setAccount(Account::constructFrom([
        'id' => 'acct_test123456',
        'object' => 'account',
        'details_submitted' => true,
    ]));

    $this->actingAs($user)
        ->get(route('stripe-connect.return'))
        ->assertRedirect(route('home'))
        ->assertSessionHasNoErrors();

    expect($user->fresh()->isStripeAccountActive())->toBeTrue();
});

it('redirects to root when the complete route is not registered', function () {
    config(['stripe_connect.routes.account.complete' => 'missing.route']);
    config(['stripe_connect.security.allowed_complete_routes' => null]);

    $user = $this->makePayableUser();

    $this->actingAs($user)
        ->get(route('stripe-connect.return'))
        ->assertRedirect('/');
});

it('redirects safely when completion route is not allowlisted', function () {
    config(['stripe_connect.routes.account.complete' => 'dashboard']);
    config(['stripe_connect.security.allowed_complete_routes' => ['home']]);

    $user = $this->makePayableUser();

    $this->actingAs($user)
        ->get(route('stripe-connect.return'))
        ->assertRedirect(route('home'))
        ->assertSessionHas(
            'error',
            'Configured Stripe Connect completion route is not allowlisted.'
        );
});

it('does not expose stripe api errors to the end user', function () {
    $user = $this->makePayableUser();

    $this->app->instance(
        StripeConnect::class,
        new class
        {
            public object $accounts;

            public function __construct()
            {
                $this->accounts = new class
                {
                    public function retrieve(string $id): void
                    {
                        throw InvalidRequestException::factory(
                            'No such account',
                            404
                        );
                    }
                };
            }
        }
    );

    $this->actingAs($user)
        ->get(route('stripe-connect.return'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error');

    expect(session('error'))->not->toContain('No such account');
});

it('blocks users without a stripe account before reaching the controller', function () {
    $user = PayableUser::query()->create([]);

    $this->actingAs($user)
        ->get(route('stripe-connect.return'))
        ->assertForbidden();
});
