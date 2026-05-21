<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use MrThito\LaravelStripeConnect\Contracts\Payable;
use MrThito\LaravelStripeConnect\Http\Controllers\Concerns\HandlesStripeConnectResponses;
use MrThito\LaravelStripeConnect\Support\StripeSecurity;
use Throwable;

/**
 * Handles Stripe's return URL after onboarding and syncs account status locally.
 */
class AccountReturnController extends Controller
{
    use HandlesStripeConnectResponses;

    public function __invoke(): RedirectResponse
    {
        try {
            /** @var Payable $user */
            $user = Auth::user();

            $account = $user->retrieveStripeAccount();

            $user->setStripeAccountStatus((bool) $account->details_submitted)->save();

            $completeRoute = Config::string('stripe_connect.routes.account.complete');

            StripeSecurity::assertAllowedCompleteRoute(
                $completeRoute,
                Config::get('stripe_connect.security.allowed_complete_routes')
            );

            if (! Route::has($completeRoute)) {
                return redirect('/');
            }

            return redirect()->route($completeRoute);
        } catch (Throwable $exception) {
            return $this->redirectOnStripeFailure($exception);
        }
    }
}
