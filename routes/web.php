<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FundingApplicationController;
use App\Http\Controllers\BusinessTransactionController;
use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\TransactionSyncController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/language/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['en', 'sw'], true), 404);

    session(['locale' => $locale]);

    return redirect()->back();
})->name('language.switch');

Route::get('/brand/logo', function () {
    $logo = storage_path('logs/logo.jpg');

    abort_unless(is_file($logo), 404);

    return response()->file($logo, ['Cache-Control' => 'public, max-age=86400']);
})->name('brand.logo');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::post('/transactions/sync', TransactionSyncController::class)->name('transactions.sync');
    Route::get('/business/profile/create', [BusinessProfileController::class, 'create'])->name('business.create');
    Route::post('/business/profile', [BusinessProfileController::class, 'store'])->name('business.store');
    Route::get('/funding/applications/create', [FundingApplicationController::class, 'create'])->name('funding.create');
    Route::post('/funding/applications', [FundingApplicationController::class, 'store'])->name('funding.store');
    Route::get('/transactions/create', [BusinessTransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [BusinessTransactionController::class, 'store'])->name('transactions.store');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
    Route::patch('/applications/{application}/status', [AdminDashboardController::class, 'updateApplication'])->name('applications.status');
    Route::patch('/providers/{provider}/status', [AdminDashboardController::class, 'updateProvider'])->name('providers.status');
    Route::patch('/hospitals/{hospital}/status', [AdminDashboardController::class, 'updateHospital'])->name('hospitals.status');
});

require __DIR__.'/auth.php';
