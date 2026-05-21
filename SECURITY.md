# Security Policy

## Supported versions

| Version | Supported |
| ------- | --------- |
| 4.x     | Yes       |
| 3.x     | No        |

## Reporting a vulnerability

Email **prashantrijal.721@gmail.com** with a description and reproduction steps. Do not open public issues for security reports.

## Threat model

This package integrates with Stripe Connect on your platform account. It does **not** replace your application’s authorization, webhook signature verification, or payout approval workflows.

### What this package protects

- Validates `STRIPE_SECRET` format and blocks test keys when `APP_ENV=production`
- Rate-limits Connect return/refresh routes per authenticated user
- Requires authenticated users implementing `MrThito\LaravelStripeConnect\Contracts\Payable`
- Requires a valid `acct_*` ID before Connect callbacks or money movement APIs run
- Allowlists Stripe account creation fields, account types, capabilities, and post-onboarding redirect route names
- Filters unsafe completion routes (open redirect hardening)
- Avoids leaking Stripe API error bodies to end users (generic flash messages + structured logs)
- Uses `redirect()->away()` for external Stripe onboarding URLs

### Your responsibilities

1. **Mass assignment** — Do not allow clients to mass-assign `StripeConnectAccount` rows; the package writes them via the Payable trait.
2. **Authorization** — Gate `createStripeAccount()`, `transfer()`, and dashboard links with policies; never expose them directly from unauthenticated routes.
3. **Transfers** — Enforce business rules (balances, KYC, fraud review) before calling `transfer()`. Configure `STRIPE_CONNECT_MAX_TRANSFER_AMOUNT` when appropriate.
4. **Webhooks** — Verify Stripe webhook signatures in your app (`Stripe-Signature` header). This package does not handle webhooks.
5. **HTTPS** — Serve your app over TLS in production; Stripe requires HTTPS for Connect redirect URLs.
6. **Secrets** — Store keys in environment variables or a secrets manager, never in version control.
7. **Idempotency** — Use Stripe idempotency keys for transfers in high-risk flows (implement in your application layer).

## Configuration checklist (production)

```env
APP_ENV=production
STRIPE_SECRET=sk_live_...
STRIPE_CONNECT_ROUTES_ENABLED=true
STRIPE_CONNECT_RATE_LIMIT=10
STRIPE_CONNECT_MAX_TRANSFER_AMOUNT=1000000
```

Publish `config/stripe_connect.php` and:

- Set `routes.middleware` to include `verified` or custom policies as needed
- Set `routes.account.connect` to your onboarding route name
- Set `security.allowed_complete_routes` to only routes you control (`null` disables allowlist checks)
- Restrict `security.allowed_account_types` to what your platform actually uses

## Dependency hygiene

Run `composer audit` in CI and keep `stripe/stripe-php` updated.
