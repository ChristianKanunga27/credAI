<?php

namespace Database\Factories;

use App\Models\InsuranceLoanApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InsuranceLoanApplication>
 */
class InsuranceLoanApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reference' => (string) Str::uuid(),
            'requested_amount' => 2500,
            'sim_balance_at_application' => 0,
            'balance_verified_at_application' => null,
            'disbursement_destination' => 'insurer',
            'status' => 'submitted',
        ];
    }
}
