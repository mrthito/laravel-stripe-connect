<?php

namespace MrThito\LaravelStripeConnect;

use Stripe\StripeClient;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use MrThito\LaravelStripeConnect\Interfaces\StripeConnect as StripeConnectInterface;

class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        $this->registerRoutes();
        $this->registerMigrations();
        $this->registerPublishing();
    }

    public function register()
    {
        $this->configure();

        App::singleton(StripeConnectInterface::class, function () {
            return new StripeClient(Config::get('stripe_connect.stripe.secret'));
        });
    }

    protected function configure()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/stripe_connect.php',
            'stripe_connect'
        );
    }

    protected function registerRoutes()
    {
        Route::middleware(['web', 'auth'])
            ->as('stripe-connect.')
            ->prefix('stripe-connect')
            ->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            });
    }

    protected function registerMigrations()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../migrations');
        }
    }

    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/stripe_connect.php' => $this->app->configPath('stripe_connect.php'),
            ], 'stripe-connect-config');

            $this->publishes([
                __DIR__ . '/../migrations' => $this->app->databasePath('migrations'),
            ], 'stripe-connect-migrations');
        }
    }
}
