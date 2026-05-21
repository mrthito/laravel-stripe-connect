<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Contracts;

use Stripe\StripeClient;

/**
 * Application binding key for the Stripe PHP SDK client.
 *
 * @mixin StripeClient
 */
interface StripeConnect {}
