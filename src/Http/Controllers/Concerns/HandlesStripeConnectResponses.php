<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use MrThito\LaravelStripeConnect\Exceptions\MissingStripeAccountException;
use MrThito\LaravelStripeConnect\Exceptions\StripeConnectException;
use Stripe\Exception\ApiErrorException;
use Throwable;

/**
 * Maps Stripe and package exceptions to safe redirects without leaking API details.
 */
trait HandlesStripeConnectResponses
{
    protected function redirectOnStripeFailure(Throwable $exception): RedirectResponse
    {
        if ($exception instanceof MissingStripeAccountException) {
            return $this->redirectToConnectFallback()
                ->with('error', $exception->getMessage());
        }

        if ($exception instanceof StripeConnectException) {
            Log::warning('stripe_connect.client_error', [
                'message' => $exception->getMessage(),
            ]);

            return $this->redirectToConnectFallback()
                ->with('error', $exception->getMessage());
        }

        if ($exception instanceof ApiErrorException) {
            Log::error('stripe_connect.api_error', [
                'type' => $exception->getStripeCode(),
                'http_status' => $exception->getHttpStatus(),
            ]);

            return $this->redirectToConnectFallback()
                ->with('error', 'Unable to complete Stripe Connect request. Please try again.');
        }

        Log::error('stripe_connect.unexpected_error', [
            'message' => $exception->getMessage(),
        ]);

        return $this->redirectToConnectFallback()
            ->with('error', 'Something went wrong. Please try again.');
    }

    protected function redirectToConnectFallback(): RedirectResponse
    {
        $route = Config::string('stripe_connect.routes.account.connect');

        if (Route::has($route)) {
            return redirect()->route($route);
        }

        return redirect('/');
    }
}
