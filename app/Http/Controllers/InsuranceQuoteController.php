<?php

namespace App\Http\Controllers;

use App\Models\InsuranceProfile;
use App\Services\InsuranceQuoteService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InsuranceQuoteController extends Controller
{
    public function __construct(
        private readonly InsuranceQuoteService $insuranceQuoteService,
        private readonly OpenAIService $openAIService
    ) {}

    public function create(): View
    {
        return view('insurance.quote');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'sim_balance' => ['nullable', 'numeric', 'min:0'],
            'coverage_goal' => ['nullable', 'string', 'in:family_protection,health_support,business_cover,travel_guard'],
        ]);

        $simBalance = (float) ($validated['sim_balance'] ?? $this->insuranceQuoteService->checkSimBalance($validated['phone']));

        $quote = $this->insuranceQuoteService->buildQuote(
            $simBalance,
            $validated['coverage_goal'] ?? 'family_protection',
            $validated['name'] ?? 'Customer'
        );
        $aiExplanation = $this->openAIService->explainQuote($quote, app()->getLocale());
        $quote['ai_summary'] = $aiExplanation ?? $quote['ai_summary'];
        $quote['ai_status'] = $aiExplanation !== null
            ? 'ready'
            : ($this->openAIService->isConfigured() ? 'unavailable' : 'rules_based');

        if ($request->user()) {
            $profile = InsuranceProfile::updateOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'full_name' => $validated['name'] ?? $request->user()->name,
                    'phone' => $validated['phone'],
                    'sim_balance' => $simBalance,
                    'coverage_goal' => $validated['coverage_goal'] ?? 'family_protection',
                    'risk_level' => $quote['risk_level'],
                    'monthly_premium' => $quote['monthly_premium'],
                    'coverage_amount' => $quote['coverage_amount'],
                    'recommended_plan' => $quote['recommended_plan'],
                    'ai_summary' => $quote['ai_summary'],
                ]
            );

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
