<?php

namespace App\Http\Controllers;

use App\Models\InsuranceLoanApplication;
use App\Models\InsurancePayment;
use App\Models\InsuranceProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InsuranceCheckoutController extends Controller
{
    public function storePayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'mobile_money_provider' => ['required', 'string', 'in:airtel_money,mpesa,tigo_pesa,halopesa'],
        ]);
        $profile = InsuranceProfile::whereBelongsTo($request->user())->latest()->firstOrFail();

        $payment = DB::transaction(function () use ($request, $validated, $profile): InsurancePayment {
            $payment = InsurancePayment::create([
                'user_id' => $request->user()->id,
                'insurance_profile_id' => $profile->id,
                'reference' => (string) Str::uuid(),
                'mobile_money_provider' => $validated['mobile_money_provider'],
                'phone' => $validated['phone'],
                'amount' => $profile->monthly_premium,
                'currency' => 'TZS',
                'status' => 'pending',
            ]);

            $this->recordActivity($request, $payment, 'insurance_payment.requested', [
                'reference' => $payment->reference,
                'amount' => $payment->amount,
            ]);

            return $payment;
        });

        return redirect()->route('dashboard')->with('status', __('Payment request :reference is pending provider confirmation.', ['reference' => $payment->reference]));
    }

    public function storeLoanApplication(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'requested_amount' => ['required', 'numeric', 'min:1000', 'max:10000000'],
            'disbursement_destination' => ['required', 'in:insurer,customer'],
            'disbursement_phone' => ['nullable', 'required_if:disbursement_destination,customer', 'string', 'max:30'],
        ]);
        $profile = InsuranceProfile::whereBelongsTo($request->user())->latest()->firstOrFail();

        if ((float) $validated['requested_amount'] > (float) $profile->sim_balance) {
            throw ValidationException::withMessages([
                'requested_amount' => __('The requested loan cannot exceed the SIM balance on your quote.'),
            ]);
        }

        $application = DB::transaction(function () use ($request, $validated, $profile): InsuranceLoanApplication {
            $application = InsuranceLoanApplication::create([
                'user_id' => $request->user()->id,
                'insurance_profile_id' => $profile->id,
                'reference' => (string) Str::uuid(),
                'requested_amount' => $validated['requested_amount'],
                'sim_balance_at_application' => $profile->sim_balance,
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
