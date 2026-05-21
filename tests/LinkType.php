<?php

declare(strict_types=1);

use MrThito\LaravelStripeConnect\Enums\LinkType;

it('exposes stripe account link types', function () {
    expect(LinkType::Onboarding->value)->toBe('account_onboarding')
        ->and(LinkType::Update->value)->toBe('account_update');
});
