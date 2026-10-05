<?php

namespace App\Http\Controllers;

use App\Models\InsuranceLoanApplication;
use App\Models\InsurancePayment;
use App\Models\InsurancePolicy;
use App\Models\InsuranceProfile;
use App\Models\MobileMoneyTransaction;
use App\Services\InsuranceQuoteService;
use App\Services\OpenAIService;
use App\Services\TransactionVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        InsuranceQuoteService $insuranceQuoteService,
        OpenAIService $openAIService,
        TransactionVerificationService $transactions,
    ): View|RedirectResponse {
        $user = auth()->user();
        $role = match ($user->role ?? 'individual') {
            'business', 'individual' => 'individual',
            'provider', 'insurer', 'hospital' => 'insurer',
            default => $user->role,
        };

        if ($role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($role === 'insurer') {
            return redirect()->route('provider.dashboard');
        }

        $profile = $user ? InsuranceProfile::where('user_id', $user->id)->latest()->first() : null;
        $mobileMoneyBalance = $transactions->availableBalanceForUser($user);
        $simBalance = $mobileMoneyBalance['transaction_count'] > 0
            ? $mobileMoneyBalance['balance']
            : 0;
        $coverageGoal = $profile ? $profile->coverage_goal : 'family_protection';
        $quote = $insuranceQuoteService->buildQuote($simBalance, $coverageGoal, $user->name ?? 'Customer');

        return view('dashboard', [
            'role' => $role,
            'roleLabel' => $role === 'individual' ? 'Normal user' : 'Insurance provider',
            'simBalance' => $simBalance,
            'mobileMoneyTransactionCount' => $mobileMoneyBalance['transaction_count'],
            'mobileMoneyVerifiedAt' => $mobileMoneyBalance['verified_at'],
            'mobileMoneyTransactions' => MobileMoneyTransaction::query()
                ->whereBelongsTo($user)
                ->where('verification_status', 'verified')
                ->latest('occurred_at')
                ->limit(5)
                ->get(['id', 'provider', 'reference', 'amount', 'transaction_type', 'occurred_at']),
            'quote' => $quote,
            'payments' => InsurancePayment::whereBelongsTo($user)
                ->with([
                    'confirmedBy:id,name',
                    'loanApplication:id,reference',
                    'providerService:id,name,insurance_provider_id',
                    'providerService.provider:id,organization_name',
                ])
                ->latest()
                ->limit(5)
                ->get(),
            'activePolicy' => InsurancePolicy::query()
                ->whereBelongsTo($user)
                ->where('status', 'active')
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->latest('start_date')
                ->first(),
            'loanApplications' => InsuranceLoanApplication::whereBelongsTo($user)
                ->with('premiumPayment:id,insurance_loan_application_id,reference,status,provider_reference')
                ->latest()
                ->limit(5)
                ->get(),
            'aiEnabled' => $openAIService->isConfigured(),
        ]);
    }
}
