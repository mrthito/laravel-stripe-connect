<?php

declare(strict_types=1);

use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('stripe-connect');
    $this->bindFakeStripe();
    $this->registerApplicationRoutes();
});

it('registers a stripe connect rate limiter', function () {
    $user = $this->makePayableUser();

    expect(RateLimiter::tooManyAttempts('stripe-connect:'.$user->getAuthIdentifier(), 2))->toBeFalse();

    RateLimiter::hit('stripe-connect:'.$user->getAuthIdentifier(), 60);
    RateLimiter::hit('stripe-connect:'.$user->getAuthIdentifier(), 60);

    expect(RateLimiter::tooManyAttempts('stripe-connect:'.$user->getAuthIdentifier(), 2))->toBeTrue();
});

it('throttles stripe connect routes after exceeding the limit', function () {
    $user = $this->makePayableUser();

    $this->actingAs($user)
        ->get(route('stripe-connect.return'))
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('stripe-connect.return'))
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('stripe-connect.return'))
        ->assertStatus(429);
});
