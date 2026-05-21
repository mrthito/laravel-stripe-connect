<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Tests;

abstract class RateLimitingTestCase extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('stripe_connect.security.rate_limit_per_minute', 2);
    }
}
