<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Reseller;
use App\Http\Controllers\KycSubmissionController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// Role-based dashboard redirect
Route::get('dashboard', function () {
    return match (auth()->user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'reseller' => redirect()->route('reseller.dashboard'),
        'client' => redirect()->route('client.dashboard'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Admin routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('dashboard', Admin\DashboardController::class)->name('dashboard');

    Route::resource('users', Admin\UserController::class);
    Route::post('users/{user}/toggle-status', [Admin\UserController::class, 'toggleStatus'])->name('users.toggle-status');

    Route::get('kyc', [Admin\KycController::class, 'index'])->name('kyc.index');
    Route::get('kyc/{kycProfile}', [Admin\KycController::class, 'show'])->name('kyc.show');
    Route::post('kyc/{kycProfile}/approve', [Admin\KycController::class, 'approve'])->name('kyc.approve');
    Route::post('kyc/{kycProfile}/reject', [Admin\KycController::class, 'reject'])->name('kyc.reject');

    Route::resource('sip-accounts', Admin\SipAccountController::class);
    Route::post('sip-accounts/{sip_account}/reprovision', [Admin\SipAccountController::class, 'reprovision'])->name('sip-accounts.reprovision');

    Route::resource('trunks', Admin\TrunkController::class);
    Route::post('trunks/{trunk}/reprovision', [Admin\TrunkController::class, 'reprovision'])->name('trunks.reprovision');

    Route::post('trunk-routes/test', [Admin\TrunkRouteController::class, 'testRoute'])->name('trunk-routes.test');
    Route::resource('trunk-routes', Admin\TrunkRouteController::class)->except(['show']);

    Route::resource('dids', Admin\DidController::class);

    Route::resource('rate-groups', Admin\RateGroupController::class);
    Route::get('rate-groups/{rate_group}/export', [Admin\RateGroupController::class, 'exportCsv'])->name('rate-groups.export');
    Route::post('rate-groups/{rate_group}/import', [Admin\RateGroupController::class, 'importCsv'])->name('rate-groups.import');
    Route::resource('rate-groups.rates', Admin\RateController::class)->except(['index', 'show']);

    Route::get('cdr', [Admin\CdrController::class, 'index'])->name('cdr.index');
    Route::get('cdr/export', [Admin\CdrController::class, 'export'])->name('cdr.export');
    Route::get('cdr/{uuid}', [Admin\CdrController::class, 'show'])->name('cdr.show');
});

// Reseller routes
Route::prefix('reseller')->name('reseller.')->middleware(['auth', 'role:reseller'])->group(function () {
    Route::get('dashboard', Reseller\DashboardController::class)->name('dashboard');

    Route::middleware('kyc.approved')->group(function () {
        Route::resource('clients', Reseller\ClientController::class)->except(['destroy']);
        Route::post('clients/{client}/toggle-status', [Reseller\ClientController::class, 'toggleStatus'])->name('clients.toggle-status');

        Route::resource('sip-accounts', Reseller\SipAccountController::class)->except(['destroy']);
        Route::post('sip-accounts/{sip_account}/reprovision', [Reseller\SipAccountController::class, 'reprovision'])->name('sip-accounts.reprovision');

        Route::get('cdr', [Reseller\CdrController::class, 'index'])->name('cdr.index');
        Route::get('cdr/export', [Reseller\CdrController::class, 'export'])->name('cdr.export');
        Route::get('cdr/{uuid}', [Reseller\CdrController::class, 'show'])->name('cdr.show');
    });
});

// Client routes
Route::prefix('client')->name('client.')->middleware(['auth', 'role:client'])->group(function () {
    Route::get('dashboard', \App\Http\Controllers\Client\DashboardController::class)->name('dashboard');
});

// KYC submission (reseller and client)
Route::middleware(['auth', 'role:reseller,client'])->group(function () {
    Route::get('kyc', [KycSubmissionController::class, 'show'])->name('kyc.show');
    Route::post('kyc', [KycSubmissionController::class, 'store'])->name('kyc.store');
    Route::post('kyc/upload', [KycSubmissionController::class, 'uploadDocument'])->name('kyc.upload');
});

require __DIR__.'/auth.php';
