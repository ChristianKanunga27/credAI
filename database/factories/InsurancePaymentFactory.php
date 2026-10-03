<?php

namespace Database\Factories;

use App\Models\InsurancePayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InsurancePayment>
 */
class InsurancePaymentFactory extends Factory
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
            'phone' => '+255712345678',
            'amount' => 2500,
            'currency' => 'TZS',
            'status' => 'pending',
        ];
    }
}
