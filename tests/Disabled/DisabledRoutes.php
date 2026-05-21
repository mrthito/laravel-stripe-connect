<?php

declare(strict_types=1);

it('does not register routes when disabled', function () {
    expect($this->app['router']->has('stripe-connect.return'))->toBeFalse()
        ->and($this->app['router']->has('stripe-connect.refresh'))->toBeFalse();
});
