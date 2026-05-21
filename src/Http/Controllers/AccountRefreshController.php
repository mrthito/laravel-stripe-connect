<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use MrThito\LaravelStripeConnect\Contracts\Payable;
use MrThito\LaravelStripeConnect\Http\Controllers\Concerns\HandlesStripeConnectResponses;
use Throwable;

/**
 * Handles Stripe's refresh URL when an account link expires and issues a new link.
 */
class AccountRefreshController extends Controller
{
    use HandlesStripeConnectResponses;

    public function __invoke(): RedirectResponse
    {
        try {
            /** @var Payable $user */
            $user = Auth::user();

            return redirect()->away($user->getStripeAccountLink());
        } catch (Throwable $exception) {
            return $this->redirectOnStripeFailure($exception);
        }
    }
}
