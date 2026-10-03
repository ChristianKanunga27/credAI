<?php

namespace App\Services;

class InsuranceQuoteService
{
    public function checkSimBalance(string $phoneNumber): float
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '0';
        $seed = (int) substr($digits, -6);

        $base = 180000;
        $adjustment = ($seed % 220000) + 70000;

        return round($base + $adjustment, 2);
    }

    public function buildQuote(float $simBalance, string $coverageGoal = 'family_protection', ?string $name = null): array
    {
        $goalLabel = match ($coverageGoal) {
            'health_support' => 'Health support',
            'business_cover' => 'Business cover',
            'travel_guard' => 'Travel guard',
            default => 'Family protection',
        };

        $rate = match ($coverageGoal) {
            'health_support' => 0.011,
            'business_cover' => 0.018,
            'travel_guard' => 0.009,
            default => 0.014,
        };

        $minimumPremium = 2500;
        $monthlyPremium = max($minimumPremium, (int) round($simBalance * $rate, 0));
        $coverageAmount = min($simBalance * 2.8, 5000000);

        return [
            'status' => 'quote_ready',
            'customer' => $name ?? 'Customer',
            'sim_balance' => round($simBalance, 2),
            'coverage_goal' => $coverageGoal,
            'goal_label' => $goalLabel,
            'monthly_premium' => $monthlyPremium,
            'coverage_amount' => round($coverageAmount, 2),
            'risk_level' => $simBalance >= 300000 ? 'low' : ($simBalance >= 180000 ? 'moderate' : 'high'),
            'recommended_plan' => $simBalance >= 300000 ? 'Smart Premium' : 'Starter Cover',
            'ai_summary' => $simBalance >= 300000
                ? 'Your balance supports a stable premium and a stronger monthly cover.'
                : 'Your balance is lower than the preferred threshold, so a leaner plan is recommended.',
        ];
    }
}
