<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Tests;

use Illuminate\Support\Facades\Route;
use MrThito\LaravelStripeConnect\ServiceProvider;
use MrThito\LaravelStripeConnect\Tests\Concerns\InteractsWithFakeStripe;
use MrThito\LaravelStripeConnect\Tests\Fixtures\PayableUser;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use InteractsWithFakeStripe;

    protected function getPackageProviders($app): array
    {
        return [
            ServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('stripe_connect.stripe.secret', 'sk_test_example123456');
        $app['config']->set('stripe_connect.stripe.key', 'pk_test_example');
        $app['config']->set('stripe_connect.routes.account.connect', 'home');
        $app['config']->set('stripe_connect.routes.account.complete', 'home');
        $app['config']->set('stripe_connect.security.allowed_complete_routes', ['home']);
        $app['config']->set('stripe_connect.security.allowed_account_types', ['express', 'standard']);
        $app['config']->set('stripe_connect.security.allowed_create_account_keys', ['type', 'country', 'email', 'metadata']);
        $app['config']->set('stripe_connect.security.allowed_capabilities', ['transfers', 'card_payments']);
        $app['config']->set('stripe_connect.security.max_transfer_amount', 500_000);
        $app['config']->set('stripe_connect.security.rate_limit_per_minute', 60);
    }

    protected function registerApplicationRoutes(): void
    {
        Route::get('/home', fn () => 'home')->name('home');
        Route::get('/dashboard', fn () => 'dashboard')->name('dashboard');
    }

    protected function makePayableUser(
        ?string $stripeAccountId = 'acct_test123456',
        bool $active = false,
    ): PayableUser {
        $user = PayableUser::query()->create([]);
        $user->stripeConnectAccount()->create([
            'stripe_account_id' => $stripeAccountId,
            'stripe_account_active' => $active,
        ]);

        return $user->fresh(['stripeConnectAccount']);
    }
}
