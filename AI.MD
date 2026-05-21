# AI assistant guide — mrthito/laravel-stripe-connect

Use this document when helping developers install, configure, or upgrade **laravel-stripe-connect** in a Laravel 13 application.

## Package summary

| Item | Value |
|------|--------|
| Composer package | `mrthito/laravel-stripe-connect` |
| PHP | `^8.3` |
| Laravel | `^13.0` |
| Service provider | `MrThito\LaravelStripeConnect\ServiceProvider` (auto-discovered) |
| Storage | Polymorphic table `stripe_connect_accounts` (`connectable_type`, `connectable_id`) |
| Recipient API | `Payable` trait + `Contracts\Payable` interface |
| Stripe client binding | `MrThito\LaravelStripeConnect\Contracts\StripeConnect` → `Stripe\StripeClient` |

Related docs in this repo: [readme.md](readme.md), [UPGRADE.md](UPGRADE.md), [SECURITY.md](SECURITY.md).

---

## Prompt: Install the package

When the user asks to **install** Stripe Connect in Laravel:

1. Confirm requirements: **PHP 8.3+**, **Laravel 13.x**, Stripe platform account with Connect enabled.

2. Run Composer:

   ```bash
   composer require mrthito/laravel-stripe-connect
   ```

3. Add environment variables to `.env`:

   ```env
   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...
   ```

   Optional:

   ```env
   STRIPE_CONNECT_ROUTES_ENABLED=true
   STRIPE_CONNECT_RATE_LIMIT=10
   STRIPE_CONNECT_MAX_TRANSFER_AMOUNT=
   ```

4. Publish assets (**migration publish is required** — the package does not auto-load migrations):

   ```bash
   php artisan vendor:publish --tag=stripe-connect-config
   php artisan vendor:publish --tag=stripe-connect-migrations
   php artisan migrate
   ```

5. Do **not** add `stripe_account_id` or `stripe_account_active` columns to `users` — this package uses a morph table.

6. Point the user to [CUSTOMIZATION.md](CUSTOMIZATION.md) for table name, custom model, and morph name options.

7. Remind the user: production must use `sk_live_*` keys; test keys are rejected when `APP_ENV=production`.

---

## Prompt: Set up the package

When the user asks to **set up** or **configure** Connect for their app:

### 1. Payable model(s)

Every payout recipient model must **implement** the contract and **use** the trait:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use MrThito\LaravelStripeConnect\Contracts\Payable as PayableContract;
use MrThito\LaravelStripeConnect\Traits\Payable;

class User extends Authenticatable implements PayableContract
{
    use Payable;
}
```

Works on any Eloquent model (`Team`, `Seller`, etc.) — one morph row per model instance in `stripe_connect_accounts`.

### 2. Publish and edit config

```bash
php artisan vendor:publish --tag=stripe-connect-config
```

Edit `config/stripe_connect.php`:

| Key | Purpose |
|-----|---------|
| `routes.enabled` | Set `false` if the app defines its own return/refresh handlers |
| `routes.middleware` | Add `verified`, policies, etc. |
| `routes.account.complete` | Named route after successful onboarding (e.g. `dashboard`) |
| `routes.account.connect` | Fallback route on errors (e.g. `connect` or `home`) |
| `security.allowed_complete_routes` | Allowlist for `complete` route (open-redirect protection); use `null` to disable |
| `security.allowed_account_types` | Usually `express` for marketplaces |
| `security.max_transfer_amount` | Max transfer in smallest currency unit (cents) |
| `account.table` / `account.model` | Override morph model/table if needed |

Add the completion route name to `security.allowed_complete_routes` when publishing config.

### 3. Onboarding flow (example)

```php
Route::get('/connect', function () {
    $recipient = auth()->user(); // must implement Payable

    if (! $recipient->getStripeAccountId()) {
        $recipient->createStripeAccount(['type' => 'express', 'country' => 'US']);
    }

    if (! $recipient->isStripeAccountActive()) {
        return redirect($recipient->getStripeAccountLink());
    }

    return redirect()->route('dashboard');
})->middleware(['auth']);
```

Built-in package routes (if enabled):

- `GET /stripe-connect/return` → `stripe-connect.return`
- `GET /stripe-connect/refresh` → `stripe-connect.refresh`

Require authenticated user implementing `Payable` and an existing `acct_*` ID for those callbacks.

### 4. Transfers

Amount is an **integer** in the smallest currency unit (cents for USD):

```php
auth()->user()->transfer(10000, 'usd'); // $100.00
```

**Always** gate `createStripeAccount()`, `transfer()`, and dashboard links with application policies — the package does not replace authorization.

### 5. Security checklist (mention to user)

- Do not mass-assign `StripeConnectAccount` from request input.
- Verify Stripe webhooks separately (not included in this package).
- Use HTTPS in production.
- Review [SECURITY.md](SECURITY.md).

### 6. Multiple models

```php
// User and Team can both use Payable; each gets its own morph row.
$team->createStripeAccount(['type' => 'express']);
$team->getStripeAccountLink();
```

Access relation: `$model->stripeConnectAccount`.

---

## Prompt: Update or upgrade the package

When the user asks to **update**, **upgrade**, or migrate from an older version:

### From Laravel 10–12 or column-based installs

1. Read [UPGRADE.md](UPGRADE.md) for breaking changes.

2. Bump dependency (major for Laravel 13 / morph storage):

   ```bash
   composer update mrthito/laravel-stripe-connect
   ```

3. **Database migration**
   - If the app previously had `stripe_account_id` / `stripe_account_active` on `users`, migrate data to `stripe_connect_accounts` then drop old columns.
   - Run:

     ```bash
     php artisan migrate
     ```

   Example data migration (adapt to the app):

   ```php
   // For each User with legacy columns:
   $user->stripeConnectAccount()->create([
       'stripe_account_id' => $user->stripe_account_id,
       'stripe_account_active' => $user->stripe_account_active,
   ]);
   ```

4. **Code changes**
   - Replace `Interfaces\StripeConnect` → `Contracts\StripeConnect`.
   - Add `implements PayableContract` on recipient models.
   - Remove reads/writes to `stripe_account_id` / `stripe_account_active` on the model; use `getStripeAccountId()`, `isStripeAccountActive()`, etc.
   - `setStripeAccountStatus(bool $status)` — must pass boolean.
   - `transfer(int $amount, string $currency)` — amount must be int.
   - Replace config `stripe_connect.payable.*` with `stripe_connect.account.*`.

5. **Config**
   - Republish if needed: `php artisan vendor:publish --tag=stripe-connect-config --force`
   - Merge new `security.*` and `routes.*` keys.
   - Register `routes.account.complete` in `security.allowed_complete_routes`.

6. **Verify**
   - Test onboarding return/refresh URLs on HTTPS staging.
   - Confirm production `STRIPE_SECRET` is `sk_live_*`.
   - Run app tests and a test Connect onboarding in Stripe test mode.

### Routine minor updates

```bash
composer update mrthito/laravel-stripe-connect
php artisan migrate
php artisan config:clear
php artisan route:clear
```

Run the package test suite when developing the package itself:

```bash
composer test
composer format:check
composer security:audit
```

---

## Common AI responses (quick reference)

| User question | Point to |
|---------------|----------|
| "How do I install?" | Composer require → `.env` keys → `php artisan migrate` |
| "User vs Team payouts?" | `Payable` on both models; morph table handles multiple types |
| "Where is stripe_account_id stored?" | `stripe_connect_accounts.stripe_account_id`, not on `users` |
| "Onboarding redirect URLs?" | Package routes or custom; config `routes.account.*` |
| "Transfer $100" | `transfer(10000, 'usd')` |
| "Upgrade from old package" | [UPGRADE.md](UPGRADE.md) + data migration + contract/config renames |
| "Production security" | [SECURITY.md](SECURITY.md) |

---

## Do not suggest

- Adding Stripe columns directly to `users` (use morph table).
- Passing raw request arrays into `createStripeAccount()` without validation (package allowlists keys; app should still authorize).
- Using test Stripe secrets in production.
- Skipping authorization on `transfer()` or account creation.
- Treating this package as a replacement for Stripe webhook signature verification.
