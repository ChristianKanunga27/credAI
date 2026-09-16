<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::query()->updateOrCreate(['email' => 'test@example.com'], [
            'name' => 'Test User',
            'role' => 'business',
            'phone' => '+234 800 555 0199',
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        DB::table('businesses')->updateOrInsert(
            ['owner_id' => $user->id, 'name' => 'Northstar Wellness Studio'],
            ['sector' => 'Health & wellness', 'capital' => 500000, 'currency' => 'NGN', 'status' => 'active', 'updated_at' => now(), 'created_at' => now()]
        );
        $business = DB::table('businesses')->where('owner_id', $user->id)->where('name', 'Northstar Wellness Studio')->first();

        DB::table('business_transactions')->updateOrInsert(['reference' => 'CRED-DEMO-001'], ['business_id' => $business->id, 'phone_number' => $user->phone, 'provider' => 'Mobile money', 'amount' => 180000, 'transaction_type' => 'credit', 'occurred_at' => now()->subDays(2), 'verification_status' => 'verified', 'updated_at' => now(), 'created_at' => now()]);
        DB::table('business_transactions')->updateOrInsert(['reference' => 'CRED-DEMO-002'], ['business_id' => $business->id, 'phone_number' => $user->phone, 'provider' => 'Bank transfer', 'amount' => 46000, 'transaction_type' => 'credit', 'occurred_at' => now()->subDay(), 'verification_status' => 'verified', 'updated_at' => now(), 'created_at' => now()]);

        $providerUser = User::query()->updateOrCreate(['email' => 'provider@example.com'], ['name' => 'Seed Fund Partners', 'role' => 'provider', 'password' => 'password']);
        DB::table('fund_providers')->updateOrInsert(['user_id' => $providerUser->id, 'organization_name' => 'Seed Fund Partners'], ['provider_type' => 'lender', 'available_funds' => 25000000, 'min_amount' => 100000, 'max_amount' => 5000000, 'status' => 'approved', 'updated_at' => now(), 'created_at' => now()]);

        $hospitalUser = User::query()->updateOrCreate(['email' => 'hospital@example.com'], ['name' => 'Mercy Care Hospital', 'role' => 'hospital', 'password' => 'password']);
        DB::table('hospitals')->updateOrInsert(['user_id' => $hospitalUser->id, 'name' => 'Mercy Care Hospital'], ['license_number' => 'MC-2048', 'status' => 'approved', 'updated_at' => now(), 'created_at' => now()]);

        User::query()->updateOrCreate(['email' => 'admin@example.com'], ['name' => 'CredAI Admin', 'role' => 'admin', 'email_verified_at' => now(), 'password' => 'password']);
    }
}
