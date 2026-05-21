<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use MrThito\LaravelStripeConnect\Contracts\StripeConnect as StripeConnectContract;
use MrThito\LaravelStripeConnect\Support\StripeSecurity;
use Stripe\StripeClient;

/**
 * Registers Stripe Connect routes, configuration, publishable assets, and the Stripe client binding.
 */
class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->registerRoutes();
        $this->registerPublishing();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/stripe_connect.php',
            'stripe_connect'
        );

        // Validate credentials before the SDK is constructed so misconfiguration fails fast.
        $this->app->singleton(StripeConnectContract::class, function (): StripeClient {
            $secret = (string) config('stripe_connect.stripe.secret');

            StripeSecurity::validateSecretKey($secret, (string) $this->app->environment());

            return new StripeClient($secret);
        });
    }

    /**
     * Protects Connect return/refresh endpoints from brute-force or accidental polling.
     */
    protected function configureRateLimiting(): void
    {
        $perMinute = (int) config('stripe_connect.security.rate_limit_per_minute', 10);

        RateLimiter::for('stripe-connect', function (Request $request) use ($perMinute) {
            return Limit::perMinute($perMinute)
                ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });
    }

    protected function registerRoutes(): void
    {
        if (! config('stripe_connect.routes.enabled', true)) {
            return;
        }

        Route::middleware(config('stripe_connect.routes.middleware', ['web', 'auth']))
            ->as('stripe-connect.')
            ->prefix('stripe-connect')
            ->group(fn () => $this->loadRoutesFrom(__DIR__.'/../routes/web.php'));
    }

    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/stripe_connect.php' => $this->app->configPath('stripe_connect.php'),
        ], 'stripe-connect-config');

        $timestamp = now()->format('Y_m_d_His');

        $this->publishes([
            __DIR__.'/../migrations/0001_01_01_000000_create_stripe_connect_accounts_table.php.stub' => $this->app->databasePath("migrations/{$timestamp}_create_stripe_connect_accounts_table.php"),
        ], 'stripe-connect-migrations');
    }
}
