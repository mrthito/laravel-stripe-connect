<?php

declare(strict_types=1);

use MrThito\LaravelStripeConnect\Tests\DisabledRoutesTestCase;
use MrThito\LaravelStripeConnect\Tests\RateLimitingTestCase;
use MrThito\LaravelStripeConnect\Tests\TestCase;

uses(TestCase::class)->in(
    'Config.php',
    'LinkType.php',
    'Middleware.php',
    'Payable.php',
    'ServiceProvider.php',
    'StripeConnectAccount.php',
    'StripeSecurity.php',
    'Controllers',
);

uses(DisabledRoutesTestCase::class)->in('Disabled');

uses(RateLimitingTestCase::class)->in('RateLimiting.php');
