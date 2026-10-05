<?php

namespace App\Services;

use App\Models\InsuranceLoanApplication;
use App\Models\InsurancePayment;
use App\Models\InsuranceProfile;
use App\Models\MobileMoneyTransaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TransactionVerificationService
{
    /**
     * @return array{status: string, imported: int, balance: float, transaction_count: int, message: string}
     */
    public function syncForUser(User $user): array
    {
        $endpoint = config('services.transaction_api.url');
        $token = config('services.transaction_api.token');

        if (! $endpoint || ! $token) {
            return [
                'status' => 'configuration_required',
                'imported' => 0,
                'balance' => 0,
                'transaction_count' => 0,
                'message' => __('Mobile-money transaction access is not configured yet.'),
            ];
        }

        if (! $user->phone) {
            return [
                'status' => 'phone_required',
                'imported' => 0,
                'balance' => 0,
                'transaction_count' => 0,
                'message' => __('Add a phone number to your profile before syncing transactions.'),
            ];
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout((int) config('services.transaction_api.timeout', 15))
                ->get($endpoint, ['phone_number' => $user->phone]);
            $response->throw();

            $transactions = $response->json('transactions');
            if (! is_array($transactions)) {
                throw new \UnexpectedValueException('Transaction provider returned an invalid transactions payload.');
            }

            $imported = 0;
            $invalid = 0;
            $business = DB::table('businesses')->where('owner_id', $user->id)->latest()->first();

            DB::transaction(function () use ($transactions, $user, $business, &$imported, &$invalid): void {
                User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

                foreach ($transactions as $transaction) {
                    if (! is_array($transaction)
                        || ! isset($transaction['reference'], $transaction['amount'], $transaction['occurred_at'])
                        || ! is_numeric($transaction['amount'])
                        || (float) $transaction['amount'] <= 0
                        || ! in_array($transaction['type'] ?? 'credit', ['credit', 'debit'], true)
                        || ! is_string($transaction['reference'])
                        || $transaction['reference'] === '') {
                        $invalid++;

                        continue;
                    }

                    try {
                        $occurredAt = Carbon::parse($transaction['occurred_at']);
                    } catch (\Throwable) {
                        $invalid++;

                        continue;
                    }

                    MobileMoneyTransaction::query()->updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'reference' => $transaction['reference'],
                        ],
                        [
                            'provider' => is_string($transaction['provider'] ?? null)
                                ? $transaction['provider']
                                : config('services.transaction_api.name', 'Mobile money'),
                            'amount' => $transaction['amount'],
                            'transaction_type' => $transaction['type'] ?? 'credit',
                            'occurred_at' => $occurredAt,
                            'verification_status' => 'verified',
                        ],
                    );
                    $imported++;

                    if ($business) {
                        DB::table('business_transactions')->updateOrInsert(
                            ['reference' => $transaction['reference']],
                            [
                                'business_id' => $business->id,
                                'phone_number' => $user->phone,
                                'provider' => is_string($transaction['provider'] ?? null)
                                    ? $transaction['provider']
                                    : config('services.transaction_api.name', 'Mobile money'),
                                'amount' => $transaction['amount'],
                                'transaction_type' => $transaction['type'] ?? 'credit',
                                'occurred_at' => $occurredAt,
                                'verification_status' => 'verified',
                                'updated_at' => now(),
                                'created_at' => now(),
                            ],
                        );
                    }
                }
            });

            if ($invalid > 0) {
                Log::warning('Mobile-money provider returned invalid transaction records', [
                    'user_id' => $user->id,
                    'invalid_count' => $invalid,
                ]);
            }

            $balance = $this->balanceForUser($user);
            $availableBalance = $this->availableBalanceForUser($user);
            if ($balance['transaction_count'] > 0) {
                $profile = InsuranceProfile::query()->whereBelongsTo($user)->latest()->first();

                if ($profile) {
                    $profile->forceFill([
                        'sim_balance' => $balance['balance'],
                        'balance_verified_at' => now(),
                    ])->save();
                }
            }
            DB::table('audit_events')->insert([
                'actor_id' => $user->id,
                'event' => 'mobile_money.transactions_synced',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'metadata' => json_encode([
                    'imported_count' => $imported,
                    'verified_transaction_count' => $balance['transaction_count'],
                    'verified_balance' => $balance['balance'],
                    'available_balance' => $availableBalance['balance'],
                    'invalid_count' => $invalid,
                ], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'status' => 'synced',
                'imported' => $imported,
                'balance' => $availableBalance['balance'],
                'transaction_count' => $balance['transaction_count'],
                'message' => $invalid > 0
                    ? __(':count transactions synced. :invalid provider records could not be verified.', [
                        'count' => $imported,
                        'invalid' => $invalid,
                    ])
                    : __(':count verified transactions synced. Available balance: TZS :balance.', [
                        'count' => $imported,
                        'balance' => number_format($availableBalance['balance'], 2),
                    ]),
            ];
        } catch (\Throwable $exception) {
            Log::error('Mobile-money transaction sync failed', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);

            return [
                'status' => 'provider_error',
                'imported' => 0,
                'balance' => 0,
                'transaction_count' => 0,
                'message' => __('The mobile-money transaction provider could not be reached. Try again later.'),
            ];
        }
    }

    /**
     * @return array{balance: float, transaction_count: int, verified_at: ?string}
     */
    public function balanceForUser(User $user): array
    {
        $transactions = MobileMoneyTransaction::query()
            ->whereBelongsTo($user)
            ->where('verification_status', 'verified');
        $credits = (float) (clone $transactions)->where('transaction_type', 'credit')->sum('amount');
        $debits = (float) (clone $transactions)->where('transaction_type', 'debit')->sum('amount');

        return [
            'balance' => max(0, $credits - $debits),
            'transaction_count' => (clone $transactions)->count(),
            'verified_at' => (clone $transactions)->max('updated_at'),
        ];
    }

    /**
     * @return array{balance: float, transaction_count: int, verified_at: ?string}
     */
    public function availableBalanceForUser(User $user): array
    {
        $balance = $this->balanceForUser($user);
        $pendingPayments = (float) InsurancePayment::query()
            ->whereBelongsTo($user)
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount');
        $paidPaymentsNotYetInTransactionHistory = (float) InsurancePayment::query()
            ->whereBelongsTo($user)
            ->where('status', 'paid')
            ->whereNotIn('provider_reference', MobileMoneyTransaction::query()
                ->select('reference')
                ->whereBelongsTo($user)
                ->where('verification_status', 'verified')
                ->where('transaction_type', 'debit'))
            ->sum('amount');
        $openLoanApplications = (float) InsuranceLoanApplication::query()
            ->whereBelongsTo($user)
            ->where(function ($query): void {
                $query->whereIn('status', ['submitted', 'under_review'])
                    ->orWhere(function ($approvedCustomerLoan): void {
                        $approvedCustomerLoan
                            ->where('status', 'approved')
                            ->where('disbursement_destination', 'customer')
                            ->whereNull('disbursement_reference');
                    });
            })
            ->sum('requested_amount');

        $balance['balance'] = max(
            0,
            $balance['balance'] - $pendingPayments - $paidPaymentsNotYetInTransactionHistory - $openLoanApplications,
        );

        return $balance;
    }

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
