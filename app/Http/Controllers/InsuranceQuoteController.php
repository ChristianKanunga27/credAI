<?php

namespace App\Http\Controllers;

use App\Models\InsuranceProfile;
use App\Services\InsuranceQuoteService;
use App\Services\OpenAIService;
use App\Services\TransactionVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InsuranceQuoteController extends Controller
{
    public function __construct(
        private readonly InsuranceQuoteService $insuranceQuoteService,
        private readonly OpenAIService $openAIService
    ) {}

    public function create(TransactionVerificationService $transactions): View
    {
        $balance = auth()->check()
            ? $transactions->availableBalanceForUser(auth()->user())
            : ['balance' => null, 'transaction_count' => 0, 'verified_at' => null];

        return view('insurance.quote', ['verifiedBalance' => $balance]);
    }

    public function store(Request $request, TransactionVerificationService $transactions)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'sim_balance' => ['nullable', 'numeric', 'min:0'],
            'coverage_goal' => ['nullable', 'string', 'in:family_protection,health_support,business_cover,travel_guard'],
        ]);

        $isCustomer = $request->user()
            && in_array($request->user()->role, ['individual', 'business'], true);
        $verifiedBalance = $isCustomer ? $transactions->availableBalanceForUser($request->user()) : null;
        $hasVerifiedTransactions = $isCustomer && $verifiedBalance['transaction_count'] > 0;
        $phone = $isCustomer && $hasVerifiedTransactions
            ? (string) $request->user()->phone
            : $validated['phone'];
        $simBalance = $hasVerifiedTransactions
            ? $verifiedBalance['balance']
            : (float) ($validated['sim_balance'] ?? $this->insuranceQuoteService->checkSimBalance($validated['phone']));

        $quote = $this->insuranceQuoteService->buildQuote(
            $simBalance,
            $validated['coverage_goal'] ?? 'family_protection',
            $validated['name'] ?? $request->user()?->name ?? 'Customer'
        );
        $aiExplanation = $this->openAIService->explainQuote($quote, app()->getLocale());
        $quote['ai_summary'] = $aiExplanation ?? $quote['ai_summary'];
        $quote['ai_status'] = $aiExplanation !== null
            ? 'ready'
            : ($this->openAIService->isConfigured() ? 'unavailable' : 'rules_based');

        if ($request->user()) {
            if ($isCustomer && ! $hasVerifiedTransactions && $request->user()->phone !== $phone) {
                $request->user()->forceFill(['phone' => $phone])->save();
            }

            $profile = InsuranceProfile::updateOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'full_name' => $validated['name'] ?? $request->user()->name,
                    'phone' => $phone,
                    'sim_balance' => $simBalance,
                    'coverage_goal' => $validated['coverage_goal'] ?? 'family_protection',
                    'risk_level' => $quote['risk_level'],
                    'monthly_premium' => $quote['monthly_premium'],
                    'coverage_amount' => $quote['coverage_amount'],
                    'recommended_plan' => $quote['recommended_plan'],
                    'ai_summary' => $quote['ai_summary'],
                ]
            );
            $profile->forceFill([
                'balance_verified_at' => $hasVerifiedTransactions ? now() : null,
            ])->save();

            DB::table('audit_events')->insert([
                'actor_id' => $request->user()->id,
                'event' => 'insurance_profile.quote_generated',
                'auditable_type' => InsuranceProfile::class,
                'auditable_id' => $profile->id,
                'metadata' => json_encode([
                    'coverage_goal' => $profile->coverage_goal,
                    'monthly_premium' => $profile->monthly_premium,
                ], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json($quote);
    }
}
