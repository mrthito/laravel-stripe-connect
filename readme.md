# Laravel Stripe Connect

This is a maintained fork of [rap2hpoutre/laravel-stripe-connect](https://github.com/rap2hpoutre/laravel-stripe-connect)
by Raphaël Huchet ([@rap2hpoutre](https://github.com/rap2hpoutre)) and later by [Simon Hamp](https://github.com/simonhamp).

With Laravel Stripe Connect, you can start your own marketplace platform using [Stripe Connect](https://stripe.com/connect)
which allows you to make transfers to your recipients directly from your Stripe account to theirs.

Laravel Stripe Connect provides a starting point to help you get your users set up and connected to your Stripe account
and start making payouts in no time.

> [!TIP]
> This package assumes that your `User` model is what will represent recipients of transfers from your platform,
> however this can be changed.

## Sponsorship

Laravel Stripe Connect is completely free to use for personal or commercial use. If it's making your job easier or you just want to
make sure it keeps being supported and improved, I'd really appreciate your donations!

[Donate now via GitHub Sponsors](https://github.com/sponsors/mrthito)

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

Run migrations:

```
php artisan migrate
```

> [!IMPORTANT]
> If you intend to use a table other than your `users` table to record your recipients' Stripe account
> details, publish the migration by running

```
php artisan vendor:publish --provider="MrThito\LaravelStripeConnect\ServiceProvider"
```

and select the appropriate

> options. You can then edit the published migration in your app's `database/migrations` folder.

## Usage

Add the `Payable` trait to any model that you consider to represent your recipient.

```php
use MrThito\LaravelStripeConnect\Traits\Payable;

class User extends Model
{
    use Payable;
```

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
