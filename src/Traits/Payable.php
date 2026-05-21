<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use MrThito\LaravelStripeConnect\Contracts\StripeConnect;
use MrThito\LaravelStripeConnect\Enums\LinkType;
use MrThito\LaravelStripeConnect\Models\StripeConnectAccount;
use MrThito\LaravelStripeConnect\Support\StripeSecurity;
use Stripe\Account;
use Stripe\Balance;
use Stripe\Transfer;

/**
 * Stripe Connect helpers for Eloquent models that represent payout recipients.
 *
 * Account details are stored on the polymorphic {@see StripeConnectAccount} model,
 * so any table (User, Team, Seller, etc.) can receive payouts without extra columns.
 *
 * @mixin Model
 */
trait Payable
{
    /** Cached Stripe API account object from the most recent SDK call. */
    protected ?Account $stripeApiAccount = null;

    /**
     * Polymorphic Stripe Connect record for this model.
     */
    public function stripeConnectAccount(): MorphOne
    {
        return $this->morphOne($this->stripeConnectAccountModel(), $this->stripeConnectMorphName());
    }

    /**
     * Creates a connected account and stores its ID on the morph record.
     *
     * Only keys listed in `stripe_connect.security.allowed_create_account_keys` are forwarded.
     *
     * @param  array<string, mixed>  $details
     */
    public function createStripeAccount(array $details): static
    {
        $payload = StripeSecurity::filterAccountCreatePayload(
            $details,
            Config::array('stripe_connect.security.allowed_create_account_keys')
        );

        if (! isset($payload['type'])) {
            $payload['type'] = 'express';
        }

        StripeSecurity::assertAllowedAccountType(
            (string) $payload['type'],
            Config::array('stripe_connect.security.allowed_account_types')
        );

        $this->stripeApiAccount = $this->stripe()->accounts->create($payload);

        $this->setStripeAccountId($this->stripeApiAccount->id);
        $this->save();

        return $this;
    }

    public function retrieveStripeAccount(): Account
    {
        $this->requireStripeAccountId();

        return $this->stripeApiAccount = $this->stripe()->accounts->retrieve(
            (string) $this->getStripeAccountId()
        );
    }

    public function getStripeAccountId(): ?string
    {
        $accountId = $this->relationLoaded('stripeConnectAccount')
            ? $this->stripeConnectAccount?->stripe_account_id
            : $this->stripeConnectAccount()->value('stripe_account_id');

        return is_string($accountId) && $accountId !== '' ? $accountId : null;
    }

    public function isStripeAccountActive(): bool
    {
        if ($this->relationLoaded('stripeConnectAccount')) {
            return (bool) $this->stripeConnectAccount?->stripe_account_active;
        }

        return (bool) $this->stripeConnectAccount()->value('stripe_account_active');
    }

    public function getStripeAccountLink(LinkType $type = LinkType::Onboarding): string
    {
        $this->requireStripeAccountId();

        $link = $this->stripe()->accountLinks->create([
            'account' => $this->getStripeAccountId(),
            'refresh_url' => URL::route(Config::string('stripe_connect.routes.account.refresh')),
            'return_url' => URL::route(Config::string('stripe_connect.routes.account.return')),
            'type' => $type->value,
        ]);

        return $link->url;
    }

    /**
     * Transfers funds from the platform balance to this connected account.
     *
     * Amount is in the currency's smallest unit (for example, cents for USD).
     */
    public function transfer(int $amount, string $currency): Transfer
    {
        $this->requireStripeAccountId();

        $normalizedCurrency = strtolower($currency);

        StripeSecurity::assertValidCurrency($normalizedCurrency);
        StripeSecurity::assertValidTransferAmount(
            $amount,
            $this->maxTransferAmount()
        );

        return $this->stripe()->transfers->create([
            'amount' => $amount,
            'currency' => $normalizedCurrency,
            'destination' => $this->getStripeAccountId(),
        ]);
    }

    public function getAccountBalance(): Balance
    {
        $this->requireStripeAccountId();

        return $this->stripe()->balance->retrieve([], [
            'stripe_account' => $this->getStripeAccountId(),
        ]);
    }

    public function setStripeAccountStatus(bool $status): static
    {
        $this->stripeConnectRecord()->update([
            'stripe_account_active' => $status,
        ]);

        if ($this->relationLoaded('stripeConnectAccount')) {
            $this->stripeConnectAccount->stripe_account_active = $status;
        }

        return $this;
    }

    public function getExpressDashboardLink(): string
    {
        $this->requireStripeAccountId();

        return $this->stripe()->accounts->createLoginLink((string) $this->getStripeAccountId())->url;
    }

    public function canAcceptCapability(string $capability = 'transfers'): bool
    {
        $this->requireStripeAccountId();

        StripeSecurity::assertAllowedCapability(
            $capability,
            Config::array('stripe_connect.security.allowed_capabilities')
        );

        return $this->stripe()->accounts->retrieveCapability(
            (string) $this->getStripeAccountId(),
            $capability
        )->status === 'active';
    }

    /**
     * Ensures a connected account exists before calling money movement APIs.
     */
    protected function requireStripeAccountId(): void
    {
        StripeSecurity::assertValidAccountId($this->getStripeAccountId());
    }

    /** Resolves the bound Stripe SDK client from the service container. */
    protected function stripe(): StripeConnect
    {
        return App::make(StripeConnect::class);
    }

    protected function setStripeAccountId(string $id): static
    {
        StripeSecurity::assertValidAccountId($id);

        $this->stripeConnectRecord()->update([
            'stripe_account_id' => $id,
        ]);

        if ($this->relationLoaded('stripeConnectAccount')) {
            $this->stripeConnectAccount->stripe_account_id = $id;
        }

        return $this;
    }

    /**
     * Returns the morph record, creating an empty row when needed.
     */
    protected function stripeConnectRecord(): StripeConnectAccount
    {
        if ($this->relationLoaded('stripeConnectAccount') && $this->stripeConnectAccount !== null) {
            return $this->stripeConnectAccount;
        }

        return $this->stripeConnectAccount()->firstOrCreate([]);
    }

    /**
     * Override on your model to use a custom StripeConnectAccount subclass.
     *
     * @return class-string<StripeConnectAccount>
     */
    protected function stripeConnectAccountModel(): string
    {
        $model = Config::get('stripe_connect.account.model');

        return is_string($model) && $model !== ''
            ? $model
            : StripeConnectAccount::class;
    }

    /**
     * Override on your model to rename the morph relation (must match your published migration).
     */
    protected function stripeConnectMorphName(): string
    {
        $name = Config::get('stripe_connect.account.morph_name');

        return is_string($name) && $name !== '' ? $name : 'connectable';
    }

    protected function maxTransferAmount(): ?int
    {
        $max = Config::get('stripe_connect.security.max_transfer_amount');

        return is_numeric($max) ? (int) $max : null;
    }
}
