<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Tests\Concerns;

use MrThito\LaravelStripeConnect\Contracts\StripeConnect;
use MrThito\LaravelStripeConnect\Tests\Fixtures\FakeStripeConnect;

trait InteractsWithFakeStripe
{
    protected FakeStripeConnect $fakeStripe;

    protected function bindFakeStripe(?FakeStripeConnect $fake = null): FakeStripeConnect
    {
        $this->fakeStripe = $fake ?? new FakeStripeConnect;

        $this->app->instance(StripeConnect::class, $this->fakeStripe);

        return $this->fakeStripe;
    }
}
