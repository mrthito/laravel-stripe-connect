<?php

declare(strict_types=1);

use MrThito\LaravelStripeConnect\Exceptions\InvalidStripeConfigurationException;
use MrThito\LaravelStripeConnect\Exceptions\InvalidStripeOperationException;
use MrThito\LaravelStripeConnect\Exceptions\MissingStripeAccountException;
use MrThito\LaravelStripeConnect\Support\StripeSecurity;

it('filters account creation payload to allowed keys', function () {
    $payload = StripeSecurity::filterAccountCreatePayload(
        [
            'type' => 'express',
            'country' => 'US',
            'malicious' => 'ignored',
        ],
        ['type', 'country']
    );

    expect($payload)->toBe(['type' => 'express', 'country' => 'US']);
});

it('accepts scalar metadata values on account creation', function () {
    $payload = StripeSecurity::filterAccountCreatePayload(
        [
            'metadata' => [
                'platform_user_id' => '42',
                'note' => 'seller',
            ],
        ],
        ['metadata']
    );

    expect($payload['metadata'])->toBe([
        'platform_user_id' => '42',
        'note' => 'seller',
    ]);
});

it('rejects non-string account types', function () {
    StripeSecurity::filterAccountCreatePayload(
        ['type' => ['express']],
        ['type']
    );
})->throws(InvalidStripeOperationException::class);

it('rejects non-array metadata', function () {
    StripeSecurity::filterAccountCreatePayload(
        ['metadata' => 'invalid'],
        ['metadata']
    );
})->throws(InvalidStripeOperationException::class);

it('rejects complex metadata values', function () {
    StripeSecurity::filterAccountCreatePayload(
        ['metadata' => ['payload' => ['nested' => true]]],
        ['metadata']
    );
})->throws(InvalidStripeOperationException::class);

it('rejects invalid stripe account ids', function () {
    StripeSecurity::assertValidAccountId('not-an-account');
})->throws(InvalidStripeOperationException::class);

it('rejects empty stripe account ids', function () {
    StripeSecurity::assertValidAccountId('');
})->throws(MissingStripeAccountException::class);

it('requires a stripe account id', function () {
    StripeSecurity::assertValidAccountId(null);
})->throws(MissingStripeAccountException::class);

it('accepts well-formed stripe account ids', function () {
    StripeSecurity::assertValidAccountId('acct_1ABCDEFGhijklmnop');

    expect(true)->toBeTrue();
});

it('rejects invalid currency codes', function () {
    StripeSecurity::assertValidCurrency('usdollars');
})->throws(InvalidStripeOperationException::class);

it('accepts lowercase iso currency codes', function () {
    StripeSecurity::assertValidCurrency('eur');

    expect(true)->toBeTrue();
});

it('rejects zero transfer amounts', function () {
    StripeSecurity::assertValidTransferAmount(0, null);
})->throws(InvalidStripeOperationException::class);

it('rejects negative transfer amounts', function () {
    StripeSecurity::assertValidTransferAmount(-1, null);
})->throws(InvalidStripeOperationException::class);

it('allows transfers within configured maximum', function () {
    StripeSecurity::assertValidTransferAmount(100, 500);

    expect(true)->toBeTrue();
});

it('rejects transfer amounts above configured maximum', function () {
    StripeSecurity::assertValidTransferAmount(10_000, 100);
})->throws(InvalidStripeOperationException::class);

it('rejects disallowed account types', function () {
    StripeSecurity::assertAllowedAccountType('custom', ['express']);
})->throws(InvalidStripeOperationException::class);

it('rejects disallowed capabilities', function () {
    StripeSecurity::assertAllowedCapability('legacy_payments', ['transfers']);
})->throws(InvalidStripeOperationException::class);

it('skips completion route checks when allowlist is null', function () {
    StripeSecurity::assertAllowedCompleteRoute('any.route', null);

    expect(true)->toBeTrue();
});

it('rejects disallowed completion routes', function () {
    StripeSecurity::assertAllowedCompleteRoute('admin.destroy', ['home']);
})->throws(InvalidStripeConfigurationException::class);

it('rejects missing stripe secrets', function () {
    StripeSecurity::validateSecretKey('', 'local');
})->throws(InvalidStripeConfigurationException::class);

it('rejects malformed stripe secrets', function () {
    StripeSecurity::validateSecretKey('not-a-secret', 'local');
})->throws(InvalidStripeConfigurationException::class);

it('rejects test secret keys in production', function () {
    StripeSecurity::validateSecretKey('sk_test_1234567890', 'production');
})->throws(InvalidStripeConfigurationException::class);

it('allows test secret keys outside production', function () {
    StripeSecurity::validateSecretKey('sk_test_1234567890', 'testing');

    expect(true)->toBeTrue();
});

it('allows live secret keys in production', function () {
    StripeSecurity::validateSecretKey('sk_live_1234567890', 'production');

    expect(true)->toBeTrue();
});
