<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Support;

use Illuminate\Support\Arr;
use MrThito\LaravelStripeConnect\Exceptions\InvalidStripeConfigurationException;
use MrThito\LaravelStripeConnect\Exceptions\InvalidStripeOperationException;
use MrThito\LaravelStripeConnect\Exceptions\MissingStripeAccountException;

/**
 * Centralizes input validation for Stripe Connect operations used by the package.
 */
final class StripeSecurity
{
    /** @see https://stripe.com/docs/api/accounts/object#account_object-id */
    private const ACCOUNT_ID_PATTERN = '/^acct_[a-zA-Z0-9]+$/';

    private const SECRET_KEY_PATTERN = '/^sk_(test|live)_[a-zA-Z0-9]+$/';

    private const CURRENCY_PATTERN = '/^[a-z]{3}$/';

    /**
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    public static function filterAccountCreatePayload(array $details, array $allowedKeys): array
    {
        $payload = Arr::only($details, $allowedKeys);

        if (isset($payload['type']) && ! is_string($payload['type'])) {
            throw new InvalidStripeOperationException('Stripe account type must be a string.');
        }

        if (isset($payload['metadata']) && ! is_array($payload['metadata'])) {
            throw new InvalidStripeOperationException('Stripe account metadata must be an array.');
        }

        foreach ($payload['metadata'] ?? [] as $key => $value) {
            if (! is_string($key) || (! is_string($value) && ! is_numeric($value))) {
                throw new InvalidStripeOperationException('Stripe account metadata values must be scalar strings.');
            }
        }

        return $payload;
    }

    public static function assertAllowedAccountType(string $type, array $allowedTypes): void
    {
        if (! in_array($type, $allowedTypes, true)) {
            throw new InvalidStripeOperationException('Stripe account type is not permitted.');
        }
    }

    public static function assertValidAccountId(?string $accountId): void
    {
        if ($accountId === null || $accountId === '') {
            throw new MissingStripeAccountException;
        }

        if (! preg_match(self::ACCOUNT_ID_PATTERN, $accountId)) {
            throw new InvalidStripeOperationException('Stripe account ID format is invalid.');
        }
    }

    public static function assertValidCurrency(string $currency): void
    {
        $normalized = strtolower($currency);

        if (! preg_match(self::CURRENCY_PATTERN, $normalized)) {
            throw new InvalidStripeOperationException('Currency must be a valid ISO 4217 code.');
        }
    }

    public static function assertValidTransferAmount(int $amount, ?int $maxAmount): void
    {
        if ($amount <= 0) {
            throw new InvalidStripeOperationException('Transfer amount must be greater than zero.');
        }

        if ($maxAmount !== null && $amount > $maxAmount) {
            throw new InvalidStripeOperationException('Transfer amount exceeds the configured maximum.');
        }
    }

    public static function assertAllowedCapability(string $capability, array $allowedCapabilities): void
    {
        if (! in_array($capability, $allowedCapabilities, true)) {
            throw new InvalidStripeOperationException('Stripe capability is not permitted.');
        }
    }

    public static function assertAllowedCompleteRoute(string $routeName, ?array $allowedRoutes): void
    {
        if ($allowedRoutes === null) {
            return;
        }

        if (! in_array($routeName, $allowedRoutes, true)) {
            throw new InvalidStripeConfigurationException('Configured Stripe Connect completion route is not allowlisted.');
        }
    }

    public static function validateSecretKey(string $secret, string $environment): void
    {
        if ($secret === '') {
            throw new InvalidStripeConfigurationException('STRIPE_SECRET is not configured.');
        }

        if (! preg_match(self::SECRET_KEY_PATTERN, $secret)) {
            throw new InvalidStripeConfigurationException('STRIPE_SECRET format is invalid.');
        }

        if ($environment === 'production' && str_starts_with($secret, 'sk_test_')) {
            throw new InvalidStripeConfigurationException('Test Stripe secret keys cannot be used in production.');
        }
    }
}
