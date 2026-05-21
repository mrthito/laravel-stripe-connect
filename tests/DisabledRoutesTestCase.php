<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Tests;

abstract class DisabledRoutesTestCase extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('stripe_connect.routes.enabled', false);
    }
}
