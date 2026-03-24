<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Client;
use App\Http\Controllers\RechargeAdmin;
use App\Http\Controllers\Reseller;
use App\Http\Controllers\Webhook;
use App\Http\Controllers\KycSubmissionController;
use App\Http\Controllers\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');

// Admin OTP Login (guest routes)
Route::prefix('admin')->name('admin.')->middleware('guest')->group(function () {
    Route::get('login', [Admin\AdminOtpLoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [Admin\AdminOtpLoginController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
    Route::get('login/verify', [Admin\AdminOtpLoginController::class, 'showOtpForm'])->name('otp.verify.form');
    Route::post('login/verify', [Admin\AdminOtpLoginController::class, 'verifyOtp'])->middleware('throttle:5,1')->name('otp.verify');
    Route::post('login/regenerate', [Admin\AdminOtpLoginController::class, 'regenerateOtp'])->name('otp.regenerate');
});
Route::post('admin/logout', [Admin\AdminOtpLoginController::class, 'logout'])->middleware('auth')->name('admin.logout');

// Impersonation routes (Super Admin only)
Route::post('admin/impersonate/{user}', [Admin\ImpersonationController::class, 'start'])->middleware('auth')->name('admin.impersonate.start');
Route::post('admin/stop-impersonation', [Admin\ImpersonationController::class, 'stop'])->middleware('auth')->name('admin.impersonate.stop');

// Role-based dashboard redirect
Route::get('dashboard', function () {
    return match (auth()->user()->role) {
        'super_admin', 'admin' => redirect()->route('admin.dashboard'),
        'recharge_admin' => redirect()->route('recharge-admin.dashboard'),
        'reseller' => redirect()->route('reseller.dashboard'),
        'client' => redirect()->route('client.dashboard'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Super Admin ONLY routes (global system features)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super_admin'])->group(function () {
    // Super Admin Management
    Route::resource('super-admins', Admin\SuperAdminController::class);

    // Regular Admin Management
    Route::resource('admins', Admin\AdminAssignmentController::class);

    // Recharge Admin Management
    Route::resource('recharge-admins', Admin\RechargeAdminController::class);

    // Trunks & Routing (global system)
    Route::resource('trunks', Admin\TrunkController::class);
    Route::post('trunks/{trunk}/reprovision', [Admin\TrunkController::class, 'reprovision'])->name('trunks.reprovision');
    Route::post('trunk-routes/test', [Admin\TrunkRouteController::class, 'testRoute'])->name('trunk-routes.test');
    Route::resource('trunk-routes', Admin\TrunkRouteController::class)->except(['show']);

    // Rate Groups & Rates (global system)
    Route::resource('rate-groups', Admin\RateGroupController::class);
    Route::get('rate-groups/{rate_group}/export', [Admin\RateGroupController::class, 'exportCsv'])->name('rate-groups.export');
    Route::post('rate-groups/{rate_group}/import', [Admin\RateGroupController::class, 'importCsv'])->name('rate-groups.import');
    Route::resource('rate-groups.rates', Admin\RateController::class)->except(['index']);

    // Audit logs
    Route::get('audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/{auditLog}', [Admin\AuditLogController::class, 'show'])->name('audit-logs.show');

    // Destination blacklist/whitelist
    Route::resource('blacklist', Admin\BlacklistController::class)->except(['show']);
    Route::resource('whitelist', Admin\WhitelistController::class)->except(['show']);

    // Transfer logs
    Route::get('transfer-logs', [Admin\TransferLogController::class, 'index'])->name('transfer-logs.index');
    Route::get('transfer-logs/{transferLog}', [Admin\TransferLogController::class, 'show'])->name('transfer-logs.show');

    // Rate imports history
    Route::get('rate-imports', [Admin\RateImportController::class, 'index'])->name('rate-imports.index');
    Route::get('rate-imports/{rateImport}', [Admin\RateImportController::class, 'show'])->name('rate-imports.show');

    // System settings
    Route::get('settings', [Admin\SystemSettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [Admin\SystemSettingController::class, 'update'])->name('settings.update');

    // Webhook endpoints
    Route::resource('webhooks', Admin\WebhookEndpointController::class);
    Route::post('webhooks/{webhook}/regenerate-secret', [Admin\WebhookEndpointController::class, 'regenerateSecret'])->name('webhooks.regenerate-secret');

    // Bulk import
    Route::get('bulk-import', [Admin\BulkImportController::class, 'index'])->name('bulk-import.index');
    Route::post('bulk-import/users', [Admin\BulkImportController::class, 'importUsers'])->name('bulk-import.users');
    Route::post('bulk-import/sip-accounts', [Admin\BulkImportController::class, 'importSipAccounts'])->name('bulk-import.sip-accounts');
    Route::post('bulk-import/dids', [Admin\BulkImportController::class, 'importDids'])->name('bulk-import.dids');
});

// Admin routes (both super_admin and admin) - scoped to assigned resellers for regular admins
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('dashboard', Admin\DashboardController::class)->name('dashboard');

    Route::resource('users', Admin\UserController::class);
    Route::post('users/{user}/toggle-status', [Admin\UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('users/{user}/adjust-balance', [Admin\UserController::class, 'adjustBalance'])->name('users.adjust-balance');

    Route::get('kyc', [Admin\KycController::class, 'index'])->name('kyc.index');
    Route::get('kyc/{kycProfile}', [Admin\KycController::class, 'show'])->name('kyc.show');
    Route::post('kyc/{kycProfile}/approve', [Admin\KycController::class, 'approve'])->name('kyc.approve');
    Route::post('kyc/{kycProfile}/reject', [Admin\KycController::class, 'reject'])->name('kyc.reject');

    Route::resource('sip-accounts', Admin\SipAccountController::class);
    Route::post('sip-accounts/{sip_account}/reprovision', [Admin\SipAccountController::class, 'reprovision'])->name('sip-accounts.reprovision');
    Route::get('sip-accounts-export', [Admin\SipAccountController::class, 'export'])->name('sip-accounts.export');
    Route::get('sip-accounts-import', [Admin\SipAccountController::class, 'importForm'])->name('sip-accounts.import-form');
    Route::post('sip-accounts-import', [Admin\SipAccountController::class, 'import'])->name('sip-accounts.import');
    Route::get('sip-accounts-import-template', [Admin\SipAccountController::class, 'importTemplate'])->name('sip-accounts.import-template');
    Route::get('sip-accounts-search-clients', [Admin\SipAccountController::class, 'searchClients'])->name('sip-accounts.search-clients');
    Route::post('sip-accounts-registration-status', [Admin\SipAccountController::class, 'registrationStatus'])->name('sip-accounts.registration-status');

    Route::resource('ring-groups', Admin\RingGroupController::class);
    Route::resource('dids', Admin\DidController::class);

    Route::get('cdr', [Admin\CdrController::class, 'index'])->name('cdr.index');
    Route::get('cdr/export', [Admin\CdrController::class, 'export'])->name('cdr.export');
    Route::get('cdr/{uuid}', [Admin\CdrController::class, 'show'])->name('cdr.show');

    // Operational Reports
    Route::get('operational-reports', [Admin\OperationalReportController::class, 'index'])->name('operational-reports.index');
    Route::get('operational-reports/active', [Admin\OperationalReportController::class, 'activeCalls'])->name('operational-reports.active');
    Route::get('operational-reports/inbound', [Admin\OperationalReportController::class, 'inboundCalls'])->name('operational-reports.inbound');
    Route::get('operational-reports/outbound', [Admin\OperationalReportController::class, 'outboundCalls'])->name('operational-reports.outbound');
    Route::get('operational-reports/p2p', [Admin\OperationalReportController::class, 'p2pCalls'])->name('operational-reports.p2p');
    Route::get('operational-reports/summary', [Admin\OperationalReportController::class, 'summaryCalls'])->name('operational-reports.summary');
    Route::get('operational-reports/daily', [Admin\OperationalReportController::class, 'dailySummary'])->name('operational-reports.daily');
    Route::get('operational-reports/monthly', [Admin\OperationalReportController::class, 'monthlySummary'])->name('operational-reports.monthly');
    Route::get('operational-reports/hourly', [Admin\OperationalReportController::class, 'hourlySummary'])->name('operational-reports.hourly');

    // Financial management (scoped)
    Route::get('transactions', [Admin\TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/{transaction}', [Admin\TransactionController::class, 'show'])->name('transactions.show');

    Route::get('balance/create', [Admin\BalanceController::class, 'create'])->name('balance.create');
    Route::post('balance', [Admin\BalanceController::class, 'store'])->middleware('throttle:30,1')->name('balance.store');

    Route::resource('invoices', Admin\InvoiceController::class)->only(['index', 'create', 'store', 'show', 'update']);
    Route::get('invoices/{invoice}/pdf', [Admin\InvoiceController::class, 'pdf'])->name('invoices.pdf');

    Route::get('payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/{payment}', [Admin\PaymentController::class, 'show'])->name('payments.show');
});

// Recharge Admin routes (view-only access + balance operations)
Route::prefix('recharge-admin')->name('recharge-admin.')->middleware(['auth', 'role:recharge_admin'])->group(function () {
    Route::get('dashboard', RechargeAdmin\DashboardController::class)->name('dashboard');

    // Balance operations - THE ONLY WRITE OPERATIONS for recharge admin
    Route::get('balance/create', [RechargeAdmin\BalanceController::class, 'create'])->name('balance.create');
    Route::post('balance', [RechargeAdmin\BalanceController::class, 'store'])->name('balance.store');

    // View-only routes (GET only)
    Route::get('users', [RechargeAdmin\UserController::class, 'index'])->name('users.index');
    Route::get('users/{user}', [RechargeAdmin\UserController::class, 'show'])->name('users.show');

    Route::get('sip-accounts', [RechargeAdmin\SipAccountController::class, 'index'])->name('sip-accounts.index');
    Route::get('sip-accounts/{sipAccount}', [RechargeAdmin\SipAccountController::class, 'show'])->name('sip-accounts.show');

    Route::get('dids', [RechargeAdmin\DidController::class, 'index'])->name('dids.index');
    Route::get('dids/{did}', [RechargeAdmin\DidController::class, 'show'])->name('dids.show');

    Route::get('cdr', [RechargeAdmin\CdrController::class, 'index'])->name('cdr.index');
    Route::get('cdr/{uuid}', [RechargeAdmin\CdrController::class, 'show'])->name('cdr.show');

    Route::get('transactions', [RechargeAdmin\TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/{transaction}', [RechargeAdmin\TransactionController::class, 'show'])->name('transactions.show');
});

// Reseller routes
Route::prefix('reseller')->name('reseller.')->middleware(['auth', 'role:reseller'])->group(function () {
    Route::get('dashboard', Reseller\DashboardController::class)->name('dashboard');

    Route::middleware('kyc.approved')->group(function () {
        Route::resource('clients', Reseller\ClientController::class)->except(['destroy']);
        Route::post('clients/{client}/toggle-status', [Reseller\ClientController::class, 'toggleStatus'])->name('clients.toggle-status');

        Route::resource('sip-accounts', Reseller\SipAccountController::class)->except(['destroy']);
        Route::post('sip-accounts/{sip_account}/reprovision', [Reseller\SipAccountController::class, 'reprovision'])->name('sip-accounts.reprovision');

        // Rate Plan
        Route::get('base-tariff', [Reseller\TariffController::class, 'baseTariff'])->name('base-tariff');
        Route::get('base-tariff/export', [Reseller\TariffController::class, 'exportBaseTariff'])->name('base-tariff.export');
        Route::resource('tariffs', Reseller\TariffController::class);
        Route::get('tariffs/{tariff}/export', [Reseller\TariffController::class, 'exportTariff'])->name('tariffs.export');
        Route::post('tariffs/{tariff}/import', [Reseller\TariffController::class, 'importTariff'])->name('tariffs.import');
        Route::get('tariffs/{tariff}/rates/create', [Reseller\TariffController::class, 'createRate'])->name('tariffs.create-rate');
        Route::post('tariffs/{tariff}/rates', [Reseller\TariffController::class, 'addRate'])->name('tariffs.add-rate');
        Route::get('tariffs/{tariff}/rates/{rate}/edit', [Reseller\TariffController::class, 'editRate'])->name('tariffs.edit-rate');
        Route::put('tariffs/{tariff}/rates/{rate}', [Reseller\TariffController::class, 'updateRate'])->name('tariffs.update-rate');
        Route::delete('tariffs/{tariff}/rates/{rate}', [Reseller\TariffController::class, 'deleteRate'])->name('tariffs.delete-rate');

        Route::get('cdr', [Reseller\CdrController::class, 'index'])->name('cdr.index');
        Route::get('cdr/export', [Reseller\CdrController::class, 'export'])->name('cdr.export');
        Route::get('cdr/{uuid}', [Reseller\CdrController::class, 'show'])->name('cdr.show');

        // Financial
        Route::get('transactions', [Reseller\TransactionController::class, 'index'])->name('transactions.index');

        Route::get('balance/create', [Reseller\BalanceController::class, 'create'])->name('balance.create');
        Route::post('balance', [Reseller\BalanceController::class, 'store'])->name('balance.store');
    });
});

// Client routes
Route::prefix('client')->name('client.')->middleware(['auth', 'role:client'])->group(function () {
    Route::get('dashboard', Client\DashboardController::class)->name('dashboard');

    Route::middleware('kyc.approved')->group(function () {
        Route::get('sip-accounts', [Client\SipAccountController::class, 'index'])->name('sip-accounts.index');
        Route::get('sip-accounts/{sipAccount}', [Client\SipAccountController::class, 'show'])->name('sip-accounts.show');
        Route::get('sip-accounts/{sipAccount}/edit', [Client\SipAccountController::class, 'edit'])->name('sip-accounts.edit');
        Route::put('sip-accounts/{sipAccount}', [Client\SipAccountController::class, 'update'])->name('sip-accounts.update');

        Route::get('dids', [Client\DidController::class, 'index'])->name('dids.index');
        Route::get('dids/{did}', [Client\DidController::class, 'show'])->name('dids.show');

        Route::get('cdr', [Client\CdrController::class, 'index'])->name('cdr.index');
        Route::get('cdr/export', [Client\CdrController::class, 'export'])->name('cdr.export');
        Route::get('cdr/{uuid}', [Client\CdrController::class, 'show'])->name('cdr.show');

        Route::get('transactions', [Client\TransactionController::class, 'index'])->name('transactions.index');

        Route::get('invoices', [Client\InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [Client\InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('invoices/{invoice}/pdf', [Client\InvoiceController::class, 'pdf'])->name('invoices.pdf');

        // Payments / Stripe top-up
        Route::get('payments/create', [Client\PaymentController::class, 'create'])->name('payments.create');
        Route::post('payments/checkout', [Client\PaymentController::class, 'checkout'])->middleware('throttle:10,1')->name('payments.checkout');
        Route::get('payments/success', [Client\PaymentController::class, 'success'])->name('payments.success');
    });
});

// Two-factor authentication challenge (guest — user not yet authenticated)
Route::get('two-factor/challenge', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
Route::post('two-factor/verify', [TwoFactorController::class, 'verify'])->middleware('throttle:5,1')->name('two-factor.verify');

// Two-factor authentication management (authenticated)
Route::middleware('auth')->group(function () {
    Route::get('two-factor/setup', [TwoFactorController::class, 'setup'])->name('two-factor.setup');
    Route::post('two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::get('two-factor/status', [TwoFactorController::class, 'status'])->name('two-factor.status');
    Route::delete('two-factor', [TwoFactorController::class, 'disable'])->name('two-factor.disable');
});

// KYC submission (reseller and client)
Route::middleware(['auth', 'role:reseller,client'])->group(function () {
    Route::get('kyc', [KycSubmissionController::class, 'show'])->name('kyc.show');
    Route::post('kyc', [KycSubmissionController::class, 'store'])->name('kyc.store');
    Route::post('kyc/upload', [KycSubmissionController::class, 'uploadDocument'])->name('kyc.upload');
});

// Stripe webhook (no CSRF, signature verified in controller)
Route::post('webhook/stripe', [Webhook\StripeWebhookController::class, 'handle'])->name('webhook.stripe');

require __DIR__.'/auth.php';
