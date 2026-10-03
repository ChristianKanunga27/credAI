<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\BusinessTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FundingApplicationController;
use App\Http\Controllers\InsuranceCheckoutController;
use App\Http\Controllers\InsuranceQuoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProviderDashboardController;
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
    $logo = public_path('images/credai-logo.svg');

    abort_unless(is_file($logo), 404);

    return response()->file($logo, ['Cache-Control' => 'public, max-age=86400']);
})->name('brand.logo');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/insurance/quote', [InsuranceQuoteController::class, 'create'])->name('insurance.quote');
Route::post('/insurance/quote', [InsuranceQuoteController::class, 'store'])->name('insurance.quote.store');

Route::middleware(['auth', 'role:individual,business'])->prefix('insurance')->name('insurance.')->group(function () {
    Route::post('/payments', [InsuranceCheckoutController::class, 'storePayment'])->name('payments.store');
    Route::post('/loans', [InsuranceCheckoutController::class, 'storeLoanApplication'])->name('loans.store');
});

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
    Route::patch('/insurance-providers/{provider}/status', [AdminDashboardController::class, 'updateInsuranceProvider'])->name('insurance-providers.status');
    Route::patch('/policies/{policy}/status', [AdminDashboardController::class, 'updatePolicy'])->name('policies.status');
    Route::patch('/claims/{claim}/status', [AdminDashboardController::class, 'updateClaim'])->name('claims.status');
    Route::patch('/payments/{payment}/status', [AdminDashboardController::class, 'updatePayment'])->name('payments.status');
    Route::patch('/loans/{application}/review', [AdminDashboardController::class, 'reviewLoan'])->name('loans.review');
});

Route::middleware(['auth', 'role:insurer,provider,hospital'])->prefix('provider')->name('provider.')->group(function () {
    Route::get('/dashboard', [ProviderDashboardController::class, 'index'])->name('dashboard');
    Route::put('/profile', [ProviderDashboardController::class, 'store'])->name('profile.store');
});

Route::get('/ai', [AIController::class, 'index'])->name('ai.index');
Route::post('/ai/chat', [AIController::class, 'chat'])->middleware('throttle:ai-chat')->name('ai.chat');

require __DIR__.'/auth.php';
