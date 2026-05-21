<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Exceptions;

final class MissingStripeAccountException extends StripeConnectException
{
    public function __construct()
    {
        parent::__construct('A Stripe Connect account must be created before performing this action.');
    }
}
