<?php

namespace App\Services;

use App\Enums\InsurancePaymentConfirmationSource;
use App\Models\InsurancePayment;
use App\Models\InsurancePolicy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class InsurancePaymentConfirmationService
{
    public function confirm(
        InsurancePayment $payment,
        string $providerReference,
        InsurancePaymentConfirmationSource $source,
        ?User $confirmedBy = null,
    ): InsurancePayment {
        if (($source === InsurancePaymentConfirmationSource::Admin) !== ($confirmedBy !== null)) {
            throw new \InvalidArgumentException('Manual confirmation requires an administrator; gateway confirmation must not impersonate one.');
        }
        if ($confirmedBy !== null && $confirmedBy->role !== 'admin') {
            throw new \InvalidArgumentException('Manual payment confirmation is restricted to administrators.');
        }

        return DB::transaction(function () use ($payment, $providerReference, $source, $confirmedBy): InsurancePayment {
            $lockedPayment = InsurancePayment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status === 'paid') {
                if ($lockedPayment->provider_reference === $providerReference
                    && $lockedPayment->confirmation_source === $source->value) {
                    return $lockedPayment;
                }

                throw new ConflictHttpException(__('This payment has already been confirmed.'));
            }

            if (! in_array($lockedPayment->status, ['pending', 'processing'], true)) {
                throw new ConflictHttpException(__('Only pending or processing payments can be confirmed.'));
            }

            $lockedPayment->forceFill([
                'status' => 'paid',
                'provider_reference' => $providerReference,
                'confirmation_source' => $source->value,
                'confirmed_by' => $confirmedBy?->id,
                'paid_at' => now(),
            ])->save();

            if ($lockedPayment->payment_type === 'premium' && ! $lockedPayment->provider_service_id) {
                $profile = $lockedPayment->profile;
                $policy = InsurancePolicy::create([
                    'user_id' => $lockedPayment->user_id,
                    'insurance_profile_id' => $lockedPayment->insurance_profile_id,
                    'policy_number' => 'CH-'.now()->format('Y').'-'.strtoupper(bin2hex(random_bytes(5))),
                    'coverage_goal' => $profile?->coverage_goal ?? 'family_protection',
                    'premium' => $lockedPayment->amount,
                    'coverage_amount' => config('insurance.annual_coverage', 100000),
                    'status' => 'active',
                    'start_date' => $lockedPayment->paid_at->toDateString(),
                    'end_date' => $lockedPayment->paid_at->copy()->addYear()->subDay()->toDateString(),
                ]);

                $lockedPayment->forceFill(['insurance_policy_id' => $policy->id])->save();
            }

            DB::table('audit_events')->insert([
                'actor_id' => $confirmedBy?->id,
                'event' => 'insurance_payment.confirmed',
                'auditable_type' => InsurancePayment::class,
                'auditable_id' => $lockedPayment->getKey(),
                'metadata' => json_encode([
                    'reference' => $lockedPayment->reference,
                    'provider_reference' => $providerReference,
                    'confirmation_source' => $source->value,
                    'amount' => $lockedPayment->amount,
                    'currency' => $lockedPayment->currency,
                ], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $lockedPayment;
        });
    }
}
