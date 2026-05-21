<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Contracts;

use MrThito\LaravelStripeConnect\Enums\LinkType;
use Stripe\Account;

/**
 * Models that receive Connect transfers must implement this contract
 * alongside the {@see \MrThito\LaravelStripeConnect\Traits\Payable} trait.
 */
interface Payable
{
    public function getStripeAccountId(): ?string;

    public function isStripeAccountActive(): bool;

    public function retrieveStripeAccount(): Account;

    public function getStripeAccountLink(LinkType $type = LinkType::Onboarding): string;

    public function setStripeAccountStatus(bool $status): static;
}
