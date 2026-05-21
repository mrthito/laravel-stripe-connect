<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use MrThito\LaravelStripeConnect\Http\Middleware\EnsureHasStripeAccount;
use MrThito\LaravelStripeConnect\Http\Middleware\EnsurePayable;
use MrThito\LaravelStripeConnect\Tests\Fixtures\PayableUser;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('blocks users that do not implement payable', function () {
    $middleware = new EnsurePayable;

    $request = Request::create('/stripe-connect/return');
    $request->setUserResolver(fn () => new User);

    $middleware->handle($request, fn () => response('ok'));
})->throws(HttpException::class);

it('allows users that implement payable', function () {
    $middleware = new EnsurePayable;

    $request = Request::create('/stripe-connect/return');
    $request->setUserResolver(fn () => PayableUser::query()->create([]));

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getContent())->toBe('ok');
});

it('blocks users without a stripe account', function () {
    $middleware = new EnsureHasStripeAccount;

    $request = Request::create('/stripe-connect/return');
    $request->setUserResolver(fn () => PayableUser::query()->create([]));

    $middleware->handle($request, fn () => response('ok'));
})->throws(HttpException::class);

it('allows users with a valid stripe account id', function () {
    $middleware = new EnsureHasStripeAccount;
    $user = $this->makePayableUser();

    $request = Request::create('/stripe-connect/return');
    $request->setUserResolver(fn () => $user);

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getContent())->toBe('ok');
});

it('blocks users with malformed stripe account ids', function () {
    $middleware = new EnsureHasStripeAccount;

    $user = PayableUser::query()->create([]);
    $user->stripeConnectAccount()->create(['stripe_account_id' => 'bad-id']);

    $request = Request::create('/stripe-connect/return');
    $request->setUserResolver(fn () => $user->fresh('stripeConnectAccount'));

    $middleware->handle($request, fn () => response('ok'));
})->throws(HttpException::class);
