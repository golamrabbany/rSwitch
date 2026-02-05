<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

// Public: issue API token
Route::post('v1/auth/token', [V1\AuthController::class, 'token']);

// Authenticated API routes
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // Auth
    Route::get('auth/me', [V1\AuthController::class, 'me']);
    Route::post('auth/revoke', [V1\AuthController::class, 'revoke']);

    // SIP Accounts
    Route::apiResource('sip-accounts', V1\SipAccountController::class);

    // DIDs (read-only for non-admin)
    Route::get('dids', [V1\DidController::class, 'index']);
    Route::get('dids/{did}', [V1\DidController::class, 'show']);

    // CDR
    Route::get('cdr', [V1\CdrController::class, 'index']);
    Route::get('cdr/{uuid}', [V1\CdrController::class, 'show']);

    // Balance
    Route::get('balance', [V1\BalanceController::class, 'show']);
    Route::post('balance/topup', [V1\BalanceController::class, 'topup']);

    // Rate lookup
    Route::get('rates/lookup', [V1\RateLookupController::class, 'lookup']);
});
