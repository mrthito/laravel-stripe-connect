<?php

declare(strict_types=1);

use MrThito\LaravelStripeConnect\Enums\LinkType;
use MrThito\LaravelStripeConnect\Exceptions\InvalidStripeOperationException;
use MrThito\LaravelStripeConnect\Exceptions\MissingStripeAccountException;
use MrThito\LaravelStripeConnect\Models\StripeConnectAccount;
use MrThito\LaravelStripeConnect\Tests\Fixtures\PayableUser;
use Stripe\Account;

beforeEach(function () {
    $this->bindFakeStripe();
    $this->registerApplicationRoutes();
});

it('creates a stripe account and persists the account id on the morph record', function () {
    $user = PayableUser::query()->create([]);

    $user->createStripeAccount(['type' => 'express', 'country' => 'US']);

    expect($user->getStripeAccountId())->toBe('acct_newfromapi')
        ->and(StripeConnectAccount::query()->count())->toBe(1)
        ->and(StripeConnectAccount::query()->value('stripe_account_id'))->toBe('acct_newfromapi');
});

it('defaults new accounts to express when type is omitted', function () {
    $user = PayableUser::query()->create([]);

    $user->createStripeAccount(['country' => 'US']);

    expect($user->getStripeAccountId())->toBe('acct_newfromapi');
});

it('rejects disallowed account types during creation', function () {
    $user = PayableUser::query()->create([]);

    $user->createStripeAccount(['type' => 'custom']);
})->throws(InvalidStripeOperationException::class);

it('retrieves a stripe account from the api', function () {
    $user = $this->makePayableUser();
    $this->fakeStripe->setAccount(Account::constructFrom([
        'id' => 'acct_test123456',
        'object' => 'account',
        'details_submitted' => true,
    ]));

    $account = $user->retrieveStripeAccount();

    expect($account)->toBeInstanceOf(Account::class)
        ->and($account->details_submitted)->toBeTrue();
});

it('returns null when no stripe account id is stored', function () {
    $user = PayableUser::query()->create([]);

    expect($user->getStripeAccountId())->toBeNull();
});

it('reports inactive status by default', function () {
    $user = PayableUser::query()->create([]);

    expect($user->isStripeAccountActive())->toBeFalse();
});

it('can mark an account as active on the morph record', function () {
    $user = $this->makePayableUser(active: false);

    $user->setStripeAccountStatus(true);

    expect($user->fresh()->isStripeAccountActive())->toBeTrue();
});

it('builds an onboarding account link', function () {
    $user = $this->makePayableUser();

    $url = $user->getStripeAccountLink();

    expect($url)->toBe('https://connect.stripe.com/setup/test');
});

it('builds an account update link', function () {
    $user = $this->makePayableUser();

    $url = $user->getStripeAccountLink(LinkType::Update);

    expect($url)->toBe('https://connect.stripe.com/setup/test');
});

it('requires an account id before generating account links', function () {
    $user = PayableUser::query()->create([]);

    $user->getStripeAccountLink();
})->throws(MissingStripeAccountException::class);

it('creates transfers with normalized currency', function () {
    $user = $this->makePayableUser();

    $transfer = $user->transfer(2500, 'USD');

    expect($transfer->amount)->toBe(2500)
        ->and($transfer->currency)->toBe('usd')
        ->and($transfer->destination)->toBe('acct_test123456');
});

it('rejects zero or negative transfer amounts', function () {
    $user = $this->makePayableUser();

    $user->transfer(0, 'usd');
})->throws(InvalidStripeOperationException::class);

it('rejects transfers above the configured maximum', function () {
    $user = $this->makePayableUser();

    $user->transfer(500_001, 'usd');
})->throws(InvalidStripeOperationException::class);

it('rejects invalid currency codes', function () {
    $user = $this->makePayableUser();

    $user->transfer(100, 'us dollars');
})->throws(InvalidStripeOperationException::class);

it('retrieves the connected account balance', function () {
    $user = $this->makePayableUser();

    $balance = $user->getAccountBalance();

    expect($balance->available[0]->amount)->toBe(1000);
});

it('creates an express dashboard login link', function () {
    $user = $this->makePayableUser();

    expect($user->getExpressDashboardLink())->toBe('https://connect.stripe.com/express/test');
});

it('checks whether a capability is active', function () {
    $user = $this->makePayableUser();

    expect($user->canAcceptCapability('transfers'))->toBeTrue();
});

it('returns false when a capability is not active', function () {
    $this->fakeStripe->capabilityStatus = 'inactive';
    $user = $this->makePayableUser();

    expect($user->canAcceptCapability('transfers'))->toBeFalse();
});

it('rejects disallowed capability names', function () {
    $user = $this->makePayableUser();

    $user->canAcceptCapability('legacy_payments');
})->throws(InvalidStripeOperationException::class);

it('rejects invalid account ids when retrieving an account', function () {
    $user = PayableUser::query()->create([]);
    $user->stripeConnectAccount()->create(['stripe_account_id' => 'invalid']);

    $user->retrieveStripeAccount();
})->throws(InvalidStripeOperationException::class);
