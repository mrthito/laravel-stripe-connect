<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use MrThito\LaravelStripeConnect\Http\Controllers\AccountRefreshController;
use MrThito\LaravelStripeConnect\Http\Controllers\AccountReturnController;
use MrThito\LaravelStripeConnect\Http\Middleware\EnsureHasStripeAccount;

// Mounted by the service provider at /stripe-connect with auth + Payable middleware.
Route::middleware([EnsureHasStripeAccount::class])->group(function () {
    Route::get('return', AccountReturnController::class)->name('return');
    Route::get('refresh', AccountRefreshController::class)->name('refresh');
});
