<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
class TransactionVerificationService
{
    public function syncForBusiness(int $businessId, string $phoneNumber): array
    {
        $endpoint = config('services.transaction_api.url');
        $token = config('services.transaction_api.token');

        if (! $endpoint || ! $token) {
            return ['status' => 'configuration_required', 'imported' => 0, 'message' => 'Transaction API credentials are not configured.'];
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout((int) config('services.transaction_api.timeout', 15))
                ->get($endpoint, ['phone_number' => $phoneNumber]);

            $response->throw();
            $transactions = $response->json('transactions', []);
            $imported = 0;

            foreach ($transactions as $transaction) {
                if (! isset($transaction['reference'], $transaction['amount'], $transaction['occurred_at'])) {
                    continue;
                }

                DB::table('business_transactions')->updateOrInsert(
                    ['reference' => $transaction['reference']],
                    [
                        'business_id' => $businessId,
                        'phone_number' => $phoneNumber,
                        'provider' => $transaction['provider'] ?? config('services.transaction_api.name', 'External provider'),
                        'amount' => $transaction['amount'],
                        'transaction_type' => $transaction['type'] ?? 'credit',
                        'occurred_at' => $transaction['occurred_at'],
                        'verification_status' => 'verified',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                $imported++;
            }

            return ['status' => 'synced', 'imported' => $imported, 'message' => "Imported {$imported} verified transactions."];
        } catch (\Throwable $exception) {
            Log::error('Transaction provider sync failed', ['business_id' => $businessId, 'message' => $exception->getMessage()]);

            return ['status' => 'provider_error', 'imported' => 0, 'message' => 'The transaction provider could not be reached. Try again later.'];
        }
    }

    public function readiness(int $businessId): array
    {
        $business = DB::table('businesses')->find($businessId);
        $credits = (float) DB::table('business_transactions')->where('business_id', $businessId)->where('transaction_type', 'credit')->where('verification_status', 'verified')->sum('amount');
        $percentage = $business && $business->capital > 0 ? round(($credits / $business->capital) * 100, 2) : 0;

        return ['capital' => (float) ($business->capital ?? 0), 'verified_transactions' => $credits, 'percentage' => $percentage, 'eligible' => $percentage > 30];
    }
}
