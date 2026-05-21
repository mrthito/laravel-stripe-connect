<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Stores Stripe Connect credentials for any payable model via a polymorphic relation.
 */
class StripeConnectAccount extends Model
{
    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stripe_account_active' => 'boolean',
        ];
    }

    public function getTable(): string
    {
        $table = config('stripe_connect.account.table');

        return is_string($table) && $table !== '' ? $table : 'stripe_connect_accounts';
    }

    public function connectable(): MorphTo
    {
        return $this->morphTo();
    }
}
