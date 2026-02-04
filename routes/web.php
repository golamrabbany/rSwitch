<?php

use App\Http\Controllers\Admin;
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
});

// Reseller routes (placeholder)
Route::prefix('reseller')->name('reseller.')->middleware(['auth', 'role:reseller'])->group(function () {
    Route::view('dashboard', 'reseller.dashboard')->name('dashboard');
});

// Client routes (placeholder)
Route::prefix('client')->name('client.')->middleware(['auth', 'role:client'])->group(function () {
    Route::view('dashboard', 'client.dashboard')->name('dashboard');
});

// KYC submission (reseller and client)
Route::middleware(['auth', 'role:reseller,client'])->group(function () {
    Route::get('kyc', [KycSubmissionController::class, 'show'])->name('kyc.show');
    Route::post('kyc', [KycSubmissionController::class, 'store'])->name('kyc.store');
    Route::post('kyc/upload', [KycSubmissionController::class, 'uploadDocument'])->name('kyc.upload');
});

require __DIR__.'/auth.php';
