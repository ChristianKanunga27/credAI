<?php

namespace App\Http\Controllers;

use App\Models\InsuranceClaim;
use App\Models\InsuranceLoanApplication;
use App\Models\InsurancePayment;
use App\Models\InsurancePolicy;
use App\Models\InsuranceProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'userCount' => User::count(),
            'providerCount' => InsuranceProvider::count(),
            'pendingProviders' => InsuranceProvider::where('status', 'pending')->count(),
            'policyCount' => InsurancePolicy::count(),
            'activePolicies' => InsurancePolicy::where('status', 'active')->count(),
            'claimCount' => InsuranceClaim::count(),
            'pendingClaims' => InsuranceClaim::whereIn('status', ['submitted', 'under_review'])->count(),
            'paymentCount' => InsurancePayment::count(),
            'pendingPayments' => InsurancePayment::whereIn('status', ['pending', 'processing'])->count(),
            'loanCount' => InsuranceLoanApplication::count(),
            'pendingLoans' => InsuranceLoanApplication::whereIn('status', ['submitted', 'under_review'])->count(),
            'providers' => InsuranceProvider::with('user:id,name,email')->latest()->limit(10)->get(),
            'approvedProviders' => InsuranceProvider::where('status', 'approved')->orderBy('organization_name')->get(['id', 'organization_name']),
            'policies' => InsurancePolicy::with(['user:id,name', 'provider:id,organization_name'])->latest()->limit(10)->get(),
            'claims' => InsuranceClaim::with(['user:id,name', 'policy:id,policy_number'])->latest()->limit(10)->get(),
            'payments' => InsurancePayment::with('user:id,name')->latest()->limit(10)->get(),
            'loanApplications' => InsuranceLoanApplication::with(['user:id,name', 'provider:id,organization_name'])->latest()->limit(10)->get(),
            'activities' => DB::table('audit_events')->leftJoin('users', 'audit_events.actor_id', '=', 'users.id')
                ->select('audit_events.*', 'users.name as actor_name')
                ->latest('audit_events.created_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function updateInsuranceProvider(Request $request, InsuranceProvider $provider): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', 'in:pending,approved,suspended,rejected']])['status'];

        return $this->updateStatus($provider, $status, 'insurance_provider.status_updated');
    }

    public function updatePolicy(Request $request, InsurancePolicy $policy): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', 'in:draft,active,cancelled,expired']])['status'];

        return $this->updateStatus($policy, $status, 'insurance_policy.status_updated');
    }

    public function updateClaim(Request $request, InsuranceClaim $claim): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', 'in:submitted,under_review,approved,rejected,paid']])['status'];

        return $this->updateStatus($claim, $status, 'insurance_claim.status_updated');
    }

    public function updatePayment(Request $request, InsurancePayment $payment): RedirectResponse
    {
        abort_unless(in_array($payment->status, ['pending', 'processing'], true), 409);

        $validated = $request->validate([
            'status' => ['required', 'in:processing,paid,failed,cancelled'],
            'provider_reference' => ['required_if:status,paid', 'nullable', 'string', 'max:255', 'unique:insurance_payments,provider_reference,'.$payment->id],
        ]);

        DB::transaction(function () use ($payment, $validated): void {
            $payment->forceFill([
                'status' => $validated['status'],
                'provider_reference' => $validated['provider_reference'] ?? null,
                'paid_at' => $validated['status'] === 'paid' ? now() : null,
            ])->save();

            $this->recordAdminActivity($payment, 'insurance_payment.status_updated', [
                'status' => $payment->status,
                'provider_reference' => $payment->provider_reference,
            ]);
        });

        return back()->with('status', __('Status updated successfully.'));
    }

    public function reviewLoan(Request $request, InsuranceLoanApplication $application): RedirectResponse
    {
        abort_unless(in_array($application->status, ['submitted', 'under_review'], true), 409);

        $validated = $request->validate([
            'status' => ['required', 'in:under_review,approved,rejected'],
            'insurance_provider_id' => ['nullable', 'required_if:status,approved', 'integer', 'exists:insurance_providers,id'],
            'repayment_months' => ['nullable', 'required_if:status,approved', 'integer', 'min:1', 'max:60'],
            'monthly_repayment' => ['nullable', 'required_if:status,approved', 'numeric', 'min:1'],
            'decision_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validated['status'] === 'approved') {
            if (! $application->balance_verified_at_application) {
                throw ValidationException::withMessages([
                    'status' => __('Verify the customer mobile-money balance before approving this loan.'),
                ]);
            }

            $approvedProvider = InsuranceProvider::whereKey($validated['insurance_provider_id'])
                ->where('status', 'approved')
                ->exists();

            if (! $approvedProvider) {
                throw ValidationException::withMessages([
                    'insurance_provider_id' => __('Select an approved insurance provider for this loan.'),
                ]);
            }
        }

        DB::transaction(function () use ($application, $validated): void {
            $application->forceFill([
                'status' => $validated['status'],
                'insurance_provider_id' => $validated['insurance_provider_id'] ?? null,
                'repayment_months' => $validated['repayment_months'] ?? null,
                'monthly_repayment' => $validated['monthly_repayment'] ?? null,
                'decision_notes' => $validated['decision_notes'] ?? null,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ])->save();

            $this->recordAdminActivity($application, 'insurance_loan.reviewed', [
                'status' => $application->status,
                'disbursement_destination' => $application->disbursement_destination,
                'insurance_provider_id' => $application->insurance_provider_id,
                'repayment_months' => $application->repayment_months,
                'monthly_repayment' => $application->monthly_repayment,
            ]);
        });

        return back()->with('status', __('Status updated successfully.'));
    }

    private function updateStatus(Model $record, string $status, string $event): RedirectResponse
    {
        DB::transaction(function () use ($record, $status, $event): void {
            $record->forceFill(['status' => $status])->save();

            DB::table('audit_events')->insert([
                'actor_id' => auth()->id(),
                'event' => $event,
                'auditable_type' => $record::class,
                'auditable_id' => $record->getKey(),
                'metadata' => json_encode(['status' => $status], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('status', __('Status updated successfully.'));
    }

    private function recordAdminActivity(Model $record, string $event, array $metadata): void
    {
        DB::table('audit_events')->insert([
            'actor_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => $record::class,
            'auditable_id' => $record->getKey(),
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
