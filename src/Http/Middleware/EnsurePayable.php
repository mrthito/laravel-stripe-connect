<?php

declare(strict_types=1);

namespace MrThito\LaravelStripeConnect\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use MrThito\LaravelStripeConnect\Contracts\Payable;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated user implements {@see Payable} before Connect routes run.
 */
final class EnsurePayable
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user instanceof Payable) {
            abort(403, 'Authenticated user must implement Stripe Connect payable contract.');
        }

        return $next($request);
    }
}
