<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\BusinessTransactionController;
use App\Http\Controllers\ClickPesaWebhookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FundingApplicationController;
use App\Http\Controllers\GeminiChatController;
use App\Http\Controllers\InsuranceCardController;
use App\Http\Controllers\InsuranceCheckoutController;
use App\Http\Controllers\InsuranceQuoteController;
use App\Http\Controllers\MobileMoneyTransactionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProviderDashboardController;
use App\Http\Controllers\ProviderServiceController;
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
    $candidates = [
        public_path('images/logo.jpg'),
        public_path('images/logo.jpeg'),
        storage_path('logs/logo.jpg'),
    ];

    foreach ($candidates as $logo) {
        if (is_file($logo)) {
            return response()->file($logo, ['Cache-Control' => 'public, max-age=86400']);
        }
    }

    abort(404);
})->name('brand.logo');

Route::post('/clickpesa/webhook', ClickPesaWebhookController::class)
    ->name('clickpesa.webhook');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/insurance/quote', [InsuranceQuoteController::class, 'create'])->name('insurance.quote');
Route::post('/insurance/quote', [InsuranceQuoteController::class, 'store'])->name('insurance.quote.store');

Route::middleware(['auth', 'role:individual,business'])->prefix('insurance')->name('insurance.')->group(function () {
    Route::get('/card', InsuranceCardController::class)->name('card');
    Route::post('/payments', [InsuranceCheckoutController::class, 'storePayment'])->name('payments.store');
    Route::post('/loans', [InsuranceCheckoutController::class, 'storeLoanApplication'])->name('loans.store');
    Route::get('/services', [ProviderServiceController::class, 'index'])->name('services.index');
    Route::post('/services/{service}/payments', [ProviderServiceController::class, 'pay'])->name('services.pay');
});

Route::middleware('auth')->group(function () {
    Route::get('/transactions/mobile-money', [MobileMoneyTransactionController::class, 'index'])->name('transactions.mobile-money.index');
    Route::post('/transactions/sync', TransactionSyncController::class)->middleware('throttle:5,1')->name('transactions.sync');
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
    Route::get('/providers', [AdminDashboardController::class, 'providers'])->name('providers');
    Route::get('/policies', [AdminDashboardController::class, 'policies'])->name('policies');
    Route::get('/claims', [AdminDashboardController::class, 'claims'])->name('claims');
    Route::get('/payments', [AdminDashboardController::class, 'payments'])->name('payments');
    Route::get('/loans', [AdminDashboardController::class, 'loans'])->name('loans');
    Route::get('/activity', [AdminDashboardController::class, 'activity'])->name('activity');
    Route::patch('/insurance-providers/{provider}/status', [AdminDashboardController::class, 'updateInsuranceProvider'])->name('insurance-providers.status');
    Route::patch('/policies/{policy}/status', [AdminDashboardController::class, 'updatePolicy'])->name('policies.status');
    Route::patch('/claims/{claim}/status', [AdminDashboardController::class, 'updateClaim'])->name('claims.status');
    Route::patch('/payments/{payment}/status', [AdminDashboardController::class, 'updatePayment'])->name('payments.status');
    Route::patch('/loans/{application}/review', [AdminDashboardController::class, 'reviewLoan'])->name('loans.review');
});

Route::middleware(['auth', 'role:insurer,provider,hospital'])->prefix('provider')->name('provider.')->group(function () {
    Route::get('/dashboard', [ProviderDashboardController::class, 'index'])->name('dashboard');
    Route::get('/services', [ProviderServiceController::class, 'manage'])->name('services.index');
    Route::put('/profile', [ProviderDashboardController::class, 'store'])->name('profile.store');
    Route::post('/services', [ProviderServiceController::class, 'store'])->name('services.store');
    Route::patch('/services/{service}', [ProviderServiceController::class, 'update'])->name('services.update');
});

Route::get('/ai', [GeminiChatController::class, 'index'])->name('ai.index');
Route::post('/ai/chat', [GeminiChatController::class, 'chat'])->middleware('throttle:ai-chat')->name('ai.chat');

require __DIR__.'/auth.php';
