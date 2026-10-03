<?php

namespace App\Http\Controllers;

use App\Models\InsuranceLoanApplication;
use App\Models\InsurancePayment;
use App\Models\InsuranceProfile;
use App\Services\InsuranceQuoteService;
use App\Services\OpenAIService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(InsuranceQuoteService $insuranceQuoteService, OpenAIService $openAIService): View|RedirectResponse
    {
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
        $simBalance = $profile ? (float) $profile->sim_balance : ($user?->phone ? $insuranceQuoteService->checkSimBalance((string) $user->phone) : 250000);
        $coverageGoal = $profile ? $profile->coverage_goal : 'family_protection';
        $quote = $insuranceQuoteService->buildQuote($simBalance, $coverageGoal, $user->name ?? 'Customer');

        if ($profile) {
            $quote['monthly_premium'] = (int) $profile->monthly_premium ?: $quote['monthly_premium'];
            $quote['coverage_amount'] = (float) $profile->coverage_amount ?: $quote['coverage_amount'];
            $quote['risk_level'] = $profile->risk_level ?: $quote['risk_level'];
            $quote['recommended_plan'] = $profile->recommended_plan ?: $quote['recommended_plan'];
            $quote['goal_label'] = $profile->coverage_goal ? ucfirst(str_replace('_', ' ', $profile->coverage_goal)) : $quote['goal_label'];
            $quote['ai_summary'] = $profile->ai_summary ?: $quote['ai_summary'];
        }

        return view('dashboard', [
            'role' => $role,
            'roleLabel' => $role === 'individual' ? 'Normal user' : 'Insurance provider',
            'simBalance' => $simBalance,
            'quote' => $quote,
            'payments' => InsurancePayment::whereBelongsTo($user)->latest()->limit(5)->get(),
            'loanApplications' => InsuranceLoanApplication::whereBelongsTo($user)->latest()->limit(5)->get(),
            'aiEnabled' => $openAIService->isConfigured(),
        ]);
    }
}
