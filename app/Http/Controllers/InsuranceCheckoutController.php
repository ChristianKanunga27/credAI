<?php

namespace App\Http\Controllers;

use App\Enums\InsurancePaymentConfirmationSource;
use App\Exceptions\ClickPesaPaymentException;
use App\Models\InsuranceLoanApplication;
use App\Models\InsurancePayment;
use App\Models\InsuranceProfile;
use App\Models\User;
use App\Services\ClickPesaService;
use App\Services\InsurancePaymentConfirmationService;
use App\Services\TransactionVerificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InsuranceCheckoutController extends Controller
{
    public function storePayment(
        Request $request,
        ClickPesaService $clickPesa,
        InsurancePaymentConfirmationService $confirmations,
    ): RedirectResponse {
        if (! $clickPesa->isConfigured()) {
            return back()->with('error', __('ClickPesa is not configured yet. Add CLICKPESA_CLIENT_ID and CLICKPESA_API_KEY to the server environment.'));
        }

        if (! $request->exists('collection_method')) {
            $request->merge(['collection_method' => 'ussd']);
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'collection_method' => ['required', 'in:ussd,control_number'],
        ]);
        $profile = InsuranceProfile::whereBelongsTo($request->user())->latest()->firstOrFail();
        if ((float) $profile->monthly_premium <= 0) {
            throw ValidationException::withMessages([
                'phone' => __('Generate a valid quote before requesting a premium payment.'),
            ]);
        }

        $existingPayment = InsurancePayment::query()
            ->whereBelongsTo($request->user())
            ->where('insurance_profile_id', $profile->id)
            ->whereNull('provider_service_id')
            ->whereIn('status', ['pending', 'processing'])
            ->latest()
            ->first();

        if ($existingPayment) {
            if ($existingPayment->collection_method === 'control_number'
                && filled($existingPayment->clickpesa_control_number)) {
                return redirect()->route('dashboard')->with('status', __('Pay the pending annual plan using control number :number, then wait for payment confirmation.', [
                    'number' => $existingPayment->clickpesa_control_number,
                ]));
            }

            try {
                $gatewayStatus = $clickPesa->verifyPayment($existingPayment->reference);
            } catch (\Throwable $exception) {
                report($exception);

                return back()->with('error', __('An earlier payment (:reference) is still awaiting ClickPesa confirmation. Its status could not be checked right now; please wait a moment and try again.', [
                    'reference' => $existingPayment->reference,
                ]));
            }

            if (in_array($gatewayStatus['status'], ['SUCCESS', 'SETTLED'], true)) {
                $amountMatches = number_format((float) $gatewayStatus['amount'], 2, '.', '')
                    === number_format((float) $existingPayment->amount, 2, '.', '');
                if ($gatewayStatus['order_reference'] === $existingPayment->reference
                    && $gatewayStatus['currency'] === strtoupper($existingPayment->currency)
                    && $gatewayStatus['reference'] !== ''
                    && $amountMatches) {
                    $confirmations->confirm(
                        $existingPayment,
                        $gatewayStatus['reference'],
                        InsurancePaymentConfirmationSource::Gateway,
                    );

                    return redirect()->route('insurance.card')->with('status', __('Your earlier payment was confirmed. Your insurance card is ready.'));
                }

                return back()->with('error', __('ClickPesa reported success for payment :reference, but the amount or receipt could not be verified. Please contact support before paying again.', [
                    'reference' => $existingPayment->reference,
                ]));
            }

            if ($gatewayStatus['status'] === 'FAILED') {
                DB::transaction(function () use ($existingPayment): void {
                    $payment = InsurancePayment::query()->whereKey($existingPayment->id)->lockForUpdate()->firstOrFail();
                    if (in_array($payment->status, ['pending', 'processing'], true)) {
                        $payment->forceFill(['status' => 'failed'])->save();
                        DB::table('audit_events')->insert([
                            'actor_id' => null,
                            'event' => 'insurance_payment.failed',
                            'auditable_type' => InsurancePayment::class,
                            'auditable_id' => $payment->id,
                            'metadata' => json_encode([
                                'reference' => $payment->reference,
                                'confirmation_source' => 'clickpesa_status_check',
                            ], JSON_THROW_ON_ERROR),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
            } else {
                return back()->with('error', __('Payment :reference is still processing. Approve the existing request on your phone or wait for ClickPesa to finish before starting another.', [
                    'reference' => $existingPayment->reference,
                ]));
            }
        }

        $payment = DB::transaction(function () use ($request, $validated, $profile): InsurancePayment {
            $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

            if (InsurancePayment::query()->whereBelongsTo($user)
                ->where('insurance_profile_id', $profile->id)
                ->whereNull('provider_service_id')
                ->whereIn('status', ['pending', 'processing'])
                ->exists()) {
                throw ValidationException::withMessages([
                    'phone' => __('A premium payment has just started. Approve the existing request on your phone or wait for confirmation before starting another.'),
                ]);
            }

            $payment = InsurancePayment::create([
                'user_id' => $user->id,
                'insurance_profile_id' => $profile->id,
                'collection_method' => $validated['collection_method'],
                'reference' => Str::upper(Str::random(20)),
                'phone' => $validated['phone'],
                'amount' => config('insurance.annual_premium', 50400),
                'currency' => 'TZS',
                'status' => 'pending',
            ]);

            $this->recordActivity($request, $payment, 'insurance_payment.requested', [
                'reference' => $payment->reference,
                'amount' => $payment->amount,
            ]);

            return $payment;
        });

        try {
            if ($payment->collection_method === 'control_number') {
                $result = $clickPesa->createControlNumber($payment);
                $payment->forceFill([
                    'clickpesa_control_number' => $result['control_number'],
                    'status' => 'pending',
                ])->save();

                return redirect()->route('dashboard')->with('status', __('Pay TZS :amount using mobile money and control number :number. Coverage starts after ClickPesa confirms payment.', [
                    'amount' => number_format((float) $payment->amount, 0),
                    'number' => $result['control_number'],
                ]));
            }

            $result = $clickPesa->initiateMobileMoneyPayment($payment);
            $payment->forceFill([
                'status' => 'processing',
                'provider_reference' => $result['provider_reference'] ?: null,
            ])->save();
        } catch (ClickPesaPaymentException $exception) {
            $payment->forceFill(['status' => 'failed'])->save();

            if ($payment->collection_method === 'ussd' && $exception->shouldFallbackToControlNumber()) {
                try {
                    $result = $clickPesa->createControlNumber($payment);
                    $payment->forceFill([
                        'collection_method' => 'control_number',
                        'clickpesa_control_number' => $result['control_number'],
                        'status' => 'pending',
                    ])->save();

                    return redirect()->route('dashboard')->with('status', __('USSD is not active on this ClickPesa network. Pay the annual plan from your mobile-money menu using control number :number.', [
                        'number' => $result['control_number'],
                    ]));
                } catch (\Throwable $fallbackException) {
                    $payment->forceFill(['status' => 'failed'])->save();
                    if (! $fallbackException instanceof ClickPesaPaymentException) {
                        report($fallbackException);
                    }

                    return back()->with('error', $fallbackException instanceof ClickPesaPaymentException
                        ? $fallbackException->customerMessage()
                        : __('USSD and control-number payment could not be started. Please check ClickPesa BillPay settings or contact support.'));
                }
            }

            return back()->with('error', $exception->customerMessage());
        } catch (\Throwable $exception) {
            $payment->forceFill(['status' => 'failed'])->save();
            report($exception);

            return back()->with('error', __('ClickPesa could not start the payment. Please try the control-number option or contact support.'));
        }

        return redirect()->route('dashboard')->with('status', __('Approve the TZS :amount payment request on your phone to complete it.', [
            'amount' => number_format((float) $payment->amount, 2),
        ]));
    }

    public function storeLoanApplication(
        Request $request,
        TransactionVerificationService $transactions,
    ): RedirectResponse {
        $validated = $request->validate([
            'disbursement_destination' => ['required', 'in:insurer,customer'],
            'disbursement_phone' => ['nullable', 'required_if:disbursement_destination,customer', 'string', 'max:30'],
        ]);
        $profile = InsuranceProfile::whereBelongsTo($request->user())->latest()->firstOrFail();
        $annualPremium = (float) config('insurance.annual_premium', 50400);
        if ((float) $profile->monthly_premium < 1000 || (float) $profile->monthly_premium > 10000000) {
            throw ValidationException::withMessages([
                'disbursement_destination' => __('Generate a valid premium quote before applying for financing.'),
            ]);
        }

        $application = DB::transaction(function () use ($request, $validated, $profile, $transactions, $annualPremium): InsuranceLoanApplication {
            $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $balance = $transactions->availableBalanceForUser($user);

            if ($balance['transaction_count'] === 0) {
                throw ValidationException::withMessages([
                    'disbursement_destination' => __('Sync verified mobile-money transactions before applying for premium financing.'),
                ]);
            }
            if ($annualPremium > $balance['balance']) {
                throw ValidationException::withMessages([
                    'disbursement_destination' => __('The premium cannot exceed your verified mobile-money transaction balance.'),
                ]);
            }

            $application = InsuranceLoanApplication::create([
                'user_id' => $user->id,
                'insurance_profile_id' => $profile->id,
                'reference' => (string) Str::uuid(),
                'requested_amount' => $annualPremium,
                'sim_balance_at_application' => $balance['balance'],
                'balance_verified_at_application' => $profile->balance_verified_at,
                'disbursement_destination' => $validated['disbursement_destination'],
                'disbursement_phone' => $validated['disbursement_phone'] ?? null,
                'status' => 'submitted',
            ]);

            $this->recordActivity($request, $application, 'insurance_loan.submitted', [
                'reference' => $application->reference,
                'requested_amount' => $application->requested_amount,
                'disbursement_destination' => $application->disbursement_destination,
            ]);

            return $application;
        });

        return redirect()->route('dashboard')->with('status', __('Loan application :reference submitted for review.', ['reference' => $application->reference]));
    }

    private function recordActivity(Request $request, Model $record, string $event, array $metadata): void
    {
        DB::table('audit_events')->insert([
            'actor_id' => $request->user()->id,
            'event' => $event,
            'auditable_type' => $record::class,
            'auditable_id' => $record->getKey(),
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
