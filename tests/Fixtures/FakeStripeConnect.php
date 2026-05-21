<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Tests\Fixtures;

use MrThito\LaravelStripeConnect\Contracts\StripeConnect;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Balance;
use Stripe\LoginLink;
use Stripe\StripeObject;
use Stripe\Transfer;

/**
 * Test double for Stripe Connect API calls used by the Payable trait and controllers.
 */
final class FakeStripeConnect implements StripeConnect
{
    public FakeStripeAccountsService $accounts;

    public FakeStripeAccountLinksService $accountLinks;

    public FakeStripeTransfersService $transfers;

    public FakeStripeBalanceService $balance;

    public string $createdAccountId = 'acct_newfromapi';

    public string $capabilityStatus = 'active';

    public function __construct(
        private ?Account $account = null,
        public string $accountLinkUrl = 'https://connect.stripe.com/setup/test',
        public string $loginLinkUrl = 'https://connect.stripe.com/express/test',
    ) {
        $this->accounts = new FakeStripeAccountsService($this);
        $this->accountLinks = new FakeStripeAccountLinksService($this);
        $this->transfers = new FakeStripeTransfersService;
        $this->balance = new FakeStripeBalanceService;
    }

    public function account(): Account
    {
        return $this->account ?? Account::constructFrom([
            'id' => 'acct_test123456',
            'object' => 'account',
            'details_submitted' => true,
        ]);
    }

    public function setAccount(Account $account): self
    {
        $this->account = $account;

        return $this;
    }
}

final class FakeStripeAccountsService
{
    public function __construct(
        private FakeStripeConnect $client,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     */
    public function create(array $params): Account
    {
        return Account::constructFrom([
            'id' => $this->client->createdAccountId,
            'object' => 'account',
            'type' => $params['type'] ?? 'express',
        ]);
    }

    public function retrieve(string $id): Account
    {
        $existing = $this->client->account();

        return Account::constructFrom([
            'id' => $id,
            'object' => 'account',
            'details_submitted' => $existing->details_submitted ?? true,
        ]);
    }

    public function createLoginLink(string $id): LoginLink
    {
        return LoginLink::constructFrom([
            'object' => 'login_link',
            'url' => $this->client->loginLinkUrl,
            'created' => time(),
        ]);
    }

    public function retrieveCapability(string $id, string $capability): StripeObject
    {
        return StripeObject::constructFrom([
            'id' => $capability,
            'object' => 'capability',
            'status' => $this->client->capabilityStatus,
        ]);
    }
}

final class FakeStripeAccountLinksService
{
    public function __construct(
        private FakeStripeConnect $client,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     */
    public function create(array $params): AccountLink
    {
        return AccountLink::constructFrom([
            'object' => 'account_link',
            'url' => $this->client->accountLinkUrl,
            'expires_at' => time() + 300,
        ]);
    }
}

final class FakeStripeTransfersService
{
    /**
     * @param  array<string, mixed>  $params
     */
    public function create(array $params): Transfer
    {
        return Transfer::constructFrom([
            'id' => 'tr_testtransfer',
            'object' => 'transfer',
            'amount' => $params['amount'],
            'currency' => $params['currency'],
            'destination' => $params['destination'],
        ]);
    }
}

final class FakeStripeBalanceService
{
    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $opts
     */
    public function retrieve(array $params = [], array $opts = []): Balance
    {
        return Balance::constructFrom([
            'object' => 'balance',
            'available' => [
                ['amount' => 1000, 'currency' => 'usd'],
            ],
        ]);
    }
}
