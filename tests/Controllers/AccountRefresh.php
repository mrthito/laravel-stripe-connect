<?php

declare(strict_types=1);

use MrThito\LaravelStripeConnect\Tests\Fixtures\PayableUser;

beforeEach(function () {
    $this->bindFakeStripe();
    $this->registerApplicationRoutes();
});

it('redirects users to the stripe hosted account link', function () {
    $user = $this->makePayableUser();

    $this->actingAs($user)
        ->get(route('stripe-connect.refresh'))
        ->assertRedirect('https://connect.stripe.com/setup/test');
});

it('blocks users without a stripe account before reaching the controller', function () {
    $user = PayableUser::query()->create([]);

    $this->actingAs($user)
        ->get(route('stripe-connect.refresh'))
        ->assertForbidden();
});
