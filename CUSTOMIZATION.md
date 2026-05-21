# Customization guide

This package is designed so **your application owns the database schema** and can tune behavior through config — not hidden package migrations.

## Why there is only a `.stub` migration (no duplicate `.php` in the package)

| Approach | Purpose |
|----------|---------|
| `migrations/*.php.stub` | Published into **your** `database/migrations/` so you can edit columns, indexes, and table names. |
| No auto-loaded package migration | Avoids running an migration you cannot customize without conflicting with your own copy. |

**Required install steps:**

```bash
composer require mrthito/laravel-stripe-connect
php artisan vendor:publish --tag=stripe-connect-config   # recommended
php artisan vendor:publish --tag=stripe-connect-migrations   # required
php artisan migrate
```

---

## 1. Multiple recipient models (User, Team, Seller, …)

Add the trait + contract to each model:

```php
use MrThito\LaravelStripeConnect\Contracts\Payable as PayableContract;
use MrThito\LaravelStripeConnect\Traits\Payable;

class Team extends Model implements PayableContract
{
    use Payable;
}
```

Each instance gets one row in `stripe_connect_accounts` via `connectable_type` / `connectable_id`.

---

## 2. Custom table name

`.env`:

```env
STRIPE_CONNECT_ACCOUNT_TABLE=platform_stripe_accounts
```

Or in `config/stripe_connect.php` after publishing config.

The published migration reads `config('stripe_connect.account.table')` — run **publish migrations before migrate** so the correct name is used.

---

## 3. Custom morph relation name

Default morph is `connectable` → columns `connectable_type`, `connectable_id`.

To rename (e.g. `payable`):

```env
STRIPE_CONNECT_MORPH_NAME=payable
```

Update your published migration if you already ran it, or set the env var **before** first `php artisan migrate`.

Override on a single model if needed:

```php
class User extends Authenticatable implements PayableContract
{
    use Payable;

    protected function stripeConnectMorphName(): string
    {
        return 'payable';
    }
}
```

---

## 4. Extend the StripeConnectAccount model

Create your own model:

```php
namespace App\Models;

use MrThito\LaravelStripeConnect\Models\StripeConnectAccount as BaseStripeConnectAccount;

class StripeConnectAccount extends BaseStripeConnectAccount
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'onboarded_at' => 'datetime',
        ]);
    }
}
```

Register it:

```env
STRIPE_CONNECT_ACCOUNT_MODEL=App\Models\StripeConnectAccount
```

Add matching columns in your **published** migration after `vendor:publish --tag=stripe-connect-migrations`.

---

## 5. Extra database columns

1. Publish migrations: `php artisan vendor:publish --tag=stripe-connect-migrations`
2. Edit `database/migrations/*_create_stripe_connect_accounts_table.php` — add columns.
3. Extend your `StripeConnectAccount` model (casts, fillable policy: keep guarded in app).
4. Optionally override `stripeConnectRecord()` behavior in a custom trait in your app.

---

## 6. Disable package routes (use your own controllers)

`config/stripe_connect.php`:

```php
'routes' => [
    'enabled' => false,
],
```

Implement return/refresh URLs yourself; still use `Payable` methods for Stripe API calls.

---

## 7. Middleware and security allowlists

After publishing config:

```php
'routes' => [
    'middleware' => ['web', 'auth', 'verified', EnsurePayable::class, 'throttle:stripe-connect'],
],
'security' => [
    'allowed_complete_routes' => ['dashboard', 'seller.home'],
    'allowed_account_types' => ['express'],
    'max_transfer_amount' => 1_000_000,
],
```

Add your post-onboarding route name to `allowed_complete_routes`.

---

## 8. Policies (recommended)

The package does not authorize transfers or account creation. Example:

```php
public function connectStripe(User $user): bool
{
    return $user->id === auth()->id();
}

public function receiveTransfer(User $recipient, int $amount): bool
{
    return auth()->user()->canManagePayouts()
        && $recipient->isStripeAccountActive();
}
```

---

## 9. Re-publish after package updates

```bash
composer update mrthito/laravel-stripe-connect
```

Compare your published migration with the new stub in:

`vendor/mrthito/laravel-stripe-connect/migrations/0001_01_01_000000_create_stripe_connect_accounts_table.php.stub`

Merge any new indexes or columns manually — do not overwrite your customized migration blindly.

See [UPGRADE.md](UPGRADE.md) for breaking changes.
