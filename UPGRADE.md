# Upgrade Guide

## Laravel 13 / PHP 8.3

This release targets **Laravel 13** and **PHP 8.3+** only. Older Laravel and PHP versions are no longer supported.

### Breaking changes

1. **PHP 8.3+** is required.
2. **Laravel 13** is required (`illuminate/*` ^13.0).
3. The `StripeConnect` contract moved from `MrThito\LaravelStripeConnect\Interfaces` to `MrThito\LaravelStripeConnect\Contracts`.
4. `Payable::setStripeAccountStatus()` now requires a `bool` argument.
5. `Payable::transfer()` requires an `int` amount (Stripe smallest currency unit).
6. Package routes use dedicated controllers instead of inline closures.
7. Publish migrations with `--tag=stripe-connect-migrations` (provider tag publishing still works).
8. User models must `implement MrThito\LaravelStripeConnect\Contracts\Payable`.
9. Built-in routes require Payable users, rate limiting, and security allowlists (see [SECURITY.md](SECURITY.md)).
10. `createStripeAccount()` only forwards allowlisted Stripe parameters from config.
11. Post-onboarding redirects must be listed in `security.allowed_complete_routes` (or set to `null` to disable checks).
12. Stripe credentials now live on the polymorphic `stripe_connect_accounts` table instead of columns on `users`. Remove old columns and run the new migration.
13. Config `stripe_connect.payable.*` replaced with `stripe_connect.account.table` and `stripe_connect.account.model`.

### Update steps

```bash
composer require mrthito/laravel-stripe-connect:^4.0
```

Replace imports:

```php
// Before
use MrThito\LaravelStripeConnect\Interfaces\StripeConnect;

// After
use MrThito\LaravelStripeConnect\Contracts\StripeConnect;
```

Run Pint in your app if you type-hint against package classes and want consistent style.

## Development

This package uses [Pest](https://pestphp.com/) for tests.

```bash
composer test
```
