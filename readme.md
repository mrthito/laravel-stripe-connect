# Laravel Stripe Connect

This is a maintained fork of [rap2hpoutre/laravel-stripe-connect](https://github.com/rap2hpoutre/laravel-stripe-connect)
by Raphaël Huchet ([@rap2hpoutre](https://github.com/rap2hpoutre)) and later by [Simon Hamp](https://github.com/simonhamp).

With Laravel Stripe Connect, you can start your own marketplace platform using [Stripe Connect](https://stripe.com/connect)
which allows you to make transfers to your recipients directly from your Stripe account to theirs.

Laravel Stripe Connect provides a starting point to help you get your users set up and connected to your Stripe account
and start making payouts in no time.

## Requirements

- PHP 8.3+
- Laravel 13.x

## Testing

This package uses [Pest](https://pestphp.com/). Run the suite with:

```
composer test
```

## Security

Production deployments should follow [SECURITY.md](SECURITY.md). At minimum:

- Implement `MrThito\LaravelStripeConnect\Contracts\Payable` on your user (or recipient) model and use the `Payable` trait
- Do not expose `StripeConnectAccount` to untrusted mass assignment
- Authorize payouts and account creation in your own policies
- Publish config and review `security.*` allowlists

> [!TIP]
> This package assumes that your `User` model is what will represent recipients of transfers from your platform,
> however this can be changed.

> [!NOTE]
> Upgrading from Laravel 10–12? See [UPGRADE.md](UPGRADE.md).

## Sponsorship

Laravel Stripe Connect is completely free to use for personal or commercial use. If it's making your job easier or you just want to
make sure it keeps being supported and improved, I'd really appreciate your donations!

[Donate now](https://buymemomo.com/rijal)

Thank you 🙏

## Sponsors

[Laradir](https://laradir.com/?ref=laravel-stripe-connect-github) - Connecting the best Laravel Developers with the best Laravel Teams.  
[quantumweb](https://quantumweb.co/?ref=mrthito/laravel-stripe-connect-github) - A bare-metal web agency. Less layers, better results.  
[RedGalaxy](https://www.redgalaxy.co.uk) - A web application development studio based in Cambridgeshire, building solutions to help businesses improve efficiency and profitability.  
[Sevalla](https://sevalla.com/?utm_source=nativephp&utm_medium=Referral&utm_campaign=homepage) - Host and manage your applications, databases, and static sites in a single, intuitive platform.

## Installation

Install via Composer:

```
composer require mrthito/laravel-stripe-connect
```

Add your Stripe credentials in `.env`:

```
STRIPE_KEY=pk_test_XxxXXxXXX
STRIPE_SECRET=sk_test_XxxXXxXXX
```

Publish config (recommended) and the migration stub (**required** — the package does not auto-run its own migration so you can customize the schema):

```
php artisan vendor:publish --tag=stripe-connect-config
php artisan vendor:publish --tag=stripe-connect-migrations
php artisan migrate
```

> [!NOTE]
> Only a **publishable stub** ships with this package (no duplicate hidden migration). See [CUSTOMIZATION.md](CUSTOMIZATION.md) for table names, custom models, and extra columns.

## Customization

See [CUSTOMIZATION.md](CUSTOMIZATION.md) for table names, custom `StripeConnectAccount` models, morph names, disabling routes, and security allowlists.

## Usage

Add the `Payable` trait to any model that you consider to represent your recipient.

```php
use MrThito\LaravelStripeConnect\Contracts\Payable as PayableContract;
use MrThito\LaravelStripeConnect\Traits\Payable;

class User extends Model implements PayableContract
{
    use Payable;
}
```

The same trait works on any model (`Team`, `Seller`, etc.). Stripe credentials are stored on the
`stripe_connect_accounts` table via a `morphOne` relation — no extra columns on your models.

Then you can use the convenient methods available to get your recipients to set up or connect their
Stripe account to your platform.

Here's an example route that will get your user to go through the Stripe Connect onboarding flow:

```php
Route::get('/connect', function () {
    if (! auth()->user()->getStripeAccountId()) {
        auth()->user()->createStripeAccount(['type' => 'express']);
    }

    if (! auth()->user()->isStripeAccountActive()) {
        return redirect(auth()->user()->getStripeAccountLink());
    }

    return redirect('dashboard');
})->middleware(['auth']);
```

Once a user's Stripe account is all connected and active, you can start creating transfers:

```php
auth()->user()->transfer(10000, 'usd');
```

> [!NOTE]
> Stripe expects amounts in the smallest denomination for the currency (in this case, cents),
> so the above is a transfer of US$100 to the logged in user.
