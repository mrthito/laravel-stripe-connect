<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Enums;

/** Stripe Account Link types used when generating onboarding URLs. */
enum LinkType: string
{
    case Onboarding = 'account_onboarding';
    case Update = 'account_update';
}
