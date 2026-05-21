<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use MrThito\LaravelStripeConnect\Contracts\Payable;
use MrThito\LaravelStripeConnect\Exceptions\InvalidStripeOperationException;
use MrThito\LaravelStripeConnect\Exceptions\MissingStripeAccountException;
use MrThito\LaravelStripeConnect\Support\StripeSecurity;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated user already has a linked `acct_*` ID before onboarding callbacks run.
 */
final class EnsureHasStripeAccount
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Payable $user */
        $user = $request->user();

        try {
            StripeSecurity::assertValidAccountId($user->getStripeAccountId());
        } catch (MissingStripeAccountException|InvalidStripeOperationException) {
            abort(403, 'Stripe Connect account is required.');
        }

        return $next($request);
    }
}
