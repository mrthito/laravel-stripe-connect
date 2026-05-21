<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use MrThito\LaravelStripeConnect\Contracts\Payable;
use MrThito\LaravelStripeConnect\Traits\Payable as PayableTrait;

class PayableUser extends Authenticatable implements Payable
{
    use PayableTrait;

    protected $table = 'users';

    protected $guarded = [];

    public function getAuthIdentifier(): int
    {
        return (int) $this->getKey();
    }
}
