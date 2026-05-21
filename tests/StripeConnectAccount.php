<?php

declare(strict_types=1);

use MrThito\LaravelStripeConnect\Models\StripeConnectAccount;
use MrThito\LaravelStripeConnect\Tests\Fixtures\PayableUser;

it('persists morph records for payable models', function () {
    $user = PayableUser::query()->create([]);
    $user->stripeConnectAccount()->create([
        'stripe_account_id' => 'acct_morph123',
        'stripe_account_active' => true,
    ]);

    $record = StripeConnectAccount::query()->first();

    expect($record)->not->toBeNull()
        ->and($record->stripe_account_id)->toBe('acct_morph123')
        ->and($record->connectable_type)->toBe(PayableUser::class)
        ->and($record->connectable_id)->toBe($user->getKey())
        ->and($user->fresh()->getStripeAccountId())->toBe('acct_morph123')
        ->and($user->fresh()->isStripeAccountActive())->toBeTrue();
});

it('allows multiple payable model types on one table', function () {
    $user = PayableUser::query()->create([]);
    $user->stripeConnectAccount()->create([
        'stripe_account_id' => 'acct_user001',
    ]);

    expect(StripeConnectAccount::query()->count())->toBe(1)
        ->and(StripeConnectAccount::query()->where('connectable_type', PayableUser::class)->count())->toBe(1);
});

it('uses a configurable table name', function () {
    config(['stripe_connect.account.table' => 'stripe_connect_accounts']);

    expect((new StripeConnectAccount)->getTable())->toBe('stripe_connect_accounts');
});
