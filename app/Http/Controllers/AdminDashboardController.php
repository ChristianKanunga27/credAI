<?php

namespace App\Http\Controllers;

use App\Enums\InsurancePaymentConfirmationSource;
use App\Models\InsuranceClaim;
use App\Models\InsuranceLoanApplication;
use App\Models\InsurancePayment;
use App\Models\InsurancePolicy;
use App\Models\InsuranceProfile;
use App\Models\InsuranceProvider;
use App\Models\User;
use App\Services\InsurancePaymentConfirmationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
            'successfulPayments' => InsurancePayment::where('status', 'paid')->count(),
            'loanCount' => InsuranceLoanApplication::count(),
            'pendingLoans' => InsuranceLoanApplication::whereIn('status', ['submitted', 'under_review'])->count(),
        ]);
    }

    public function providers(Request $request): View
    {
        $filters = $this->filters($request, ['pending', 'approved', 'suspended', 'rejected']);
        $providers = InsuranceProvider::query()
            ->with('user:id,name,email')
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['search'], function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('organization_name', 'like', "%{$search}%")
                        ->orWhere('license_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return $this->section('providers', $providers, $filters);
    }

    public function policies(Request $request): View
    {
        $filters = $this->filters($request, ['draft', 'active', 'cancelled', 'expired']);
        $policies = InsurancePolicy::query()
            ->with(['user:id,name', 'provider:id,organization_name'])
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['search'], function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('policy_number', 'like', "%{$search}%")
                        ->orWhere('coverage_goal', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('provider', fn (Builder $providerQuery) => $providerQuery->where('organization_name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return $this->section('policies', $policies, $filters);
    }

    public function claims(Request $request): View
    {
        $filters = $this->filters($request, ['submitted', 'under_review', 'approved', 'rejected', 'paid']);
        $claims = InsuranceClaim::query()
            ->with(['user:id,name', 'policy:id,policy_number'])
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['search'], function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('claim_number', 'like', "%{$search}%")
                        ->orWhere('claim_type', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('policy', fn (Builder $policyQuery) => $policyQuery->where('policy_number', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return $this->section('claims', $claims, $filters);
    }

    public function payments(Request $request): View
    {
        $filters = $this->filters($request, ['pending', 'processing', 'paid', 'failed', 'cancelled']);
        $payments = InsurancePayment::query()
            ->with([
                'user:id,name',
                'policy:id,policy_number',
                'confirmedBy:id,name',
                'loanApplication:id,reference,insurance_provider_id',
                'loanApplication.provider:id,organization_name',
                'providerService:id,insurance_provider_id,name',
                'providerService.provider:id,organization_name,provider_type',
            ])
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['search'], function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('reference', 'like', "%{$search}%")
                        ->orWhere('provider_reference', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('providerService', fn (Builder $serviceQuery) => $serviceQuery->where('name', 'like', "%{$search}%")
                            ->orWhereHas('provider', fn (Builder $providerQuery) => $providerQuery->where('organization_name', 'like', "%{$search}%")))
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return $this->section('payments', $payments, $filters);
    }

    public function loans(Request $request): View
    {
        $filters = $this->filters($request, ['submitted', 'under_review', 'approved', 'rejected']);
        $applications = InsuranceLoanApplication::query()
            ->with(['user:id,name', 'provider:id,organization_name', 'premiumPayment:id,insurance_loan_application_id,reference,status,provider_reference'])
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['search'], function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('reference', 'like', "%{$search}%")
                        ->orWhere('disbursement_phone', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return $this->section('loans', $applications, $filters, [
            'approvedProviders' => InsuranceProvider::query()
                ->where('status', 'approved')
                ->orderBy('organization_name')
                ->get(['id', 'organization_name']),
        ]);
    }

    public function activity(Request $request): View
    {
        $filters = $this->filters($request, []);
        $activities = DB::table('audit_events')
            ->leftJoin('users', 'audit_events.actor_id', '=', 'users.id')
            ->select('audit_events.*', 'users.name as actor_name')
            ->when($filters['search'], function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('audit_events.event', 'like', "%{$search}%")
                        ->orWhere('audit_events.auditable_type', 'like', "%{$search}%")
                        ->orWhere('users.name', 'like', "%{$search}%");
                });
            })
            ->latest('audit_events.created_at')
            ->paginate(25)
            ->withQueryString();

        return $this->section('activity', $activities, $filters);
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

    public function updatePayment(
        Request $request,
        InsurancePayment $payment,
        InsurancePaymentConfirmationService $paymentConfirmation,
    ): RedirectResponse {
        abort_unless(in_array($payment->status, ['pending', 'processing'], true), 409);

        $validated = $request->validate([
            'status' => ['required', 'in:processing,paid,failed,cancelled'],
            'provider_reference' => [
                'required_if:status,paid',
                'nullable',
                'string',
                'max:255',
                'unique:insurance_payments,provider_reference,'.$payment->id,
            ],
        ]);

        if ($validated['status'] === 'paid') {
            $paymentConfirmation->confirm(
                $payment,
                $validated['provider_reference'],
                InsurancePaymentConfirmationSource::Admin,
                $request->user(),
            );
        } else {
            DB::transaction(function () use ($payment, $validated): void {
                $lockedPayment = InsurancePayment::query()
                    ->whereKey($payment->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                abort_unless(in_array($lockedPayment->status, ['pending', 'processing'], true), 409);

                $lockedPayment->forceFill([
                    'status' => $validated['status'],
                    'provider_reference' => null,
                    'confirmation_source' => null,
                    'confirmed_by' => null,
                    'paid_at' => null,
                ])->save();

                $this->recordAdminActivity($lockedPayment, 'insurance_payment.status_updated', [
                    'status' => $lockedPayment->status,
                ]);
            });
        }

        return back()->with('status', $validated['status'] === 'paid'
            ? __('Payment confirmed successfully.')
            : __('Status updated successfully.'));
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
            'verify_balance' => ['nullable', 'accepted'],
            'balance_verification_reference' => ['nullable', 'required_if:verify_balance,1', 'string', 'max:255'],
        ]);

        if ($validated['status'] === 'approved') {
            if (! $application->balance_verified_at_application && ! ($validated['verify_balance'] ?? false)) {
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
            $lockedApplication = InsuranceLoanApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless(in_array($lockedApplication->status, ['submitted', 'under_review'], true), 409);
            User::query()->whereKey($lockedApplication->user_id)->lockForUpdate()->firstOrFail();

            $balanceVerifiedAt = $lockedApplication->balance_verified_at_application;
            if ($validated['status'] === 'approved' && ! $balanceVerifiedAt) {
                if (! ($validated['verify_balance'] ?? false)) {
                    throw ValidationException::withMessages([
                        'verify_balance' => __('Verify the customer mobile-money balance before approving this loan.'),
                    ]);
                }

                $profile = InsuranceProfile::query()
                    ->whereKey($lockedApplication->insurance_profile_id)
                    ->lockForUpdate()
                    ->first();

                if (! $profile) {
                    throw ValidationException::withMessages([
                        'status' => __('The customer insurance profile is unavailable for balance verification.'),
                    ]);
                }

                $balanceVerifiedAt = now();
                $profile->forceFill(['balance_verified_at' => $balanceVerifiedAt])->save();
                $this->recordAdminActivity($profile, 'insurance_profile.balance_verified', [
                    'loan_reference' => $lockedApplication->reference,
                    'sim_balance' => $profile->sim_balance,
                    'verification_reference' => $validated['balance_verification_reference'],
                ]);
            }

            $lockedApplication->forceFill([
                'status' => $validated['status'],
                'insurance_provider_id' => $validated['insurance_provider_id'] ?? null,
                'repayment_months' => $validated['repayment_months'] ?? null,
                'monthly_repayment' => $validated['monthly_repayment'] ?? null,
                'decision_notes' => $validated['decision_notes'] ?? null,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'balance_verified_at_application' => $balanceVerifiedAt,
            ])->save();

            $this->recordAdminActivity($lockedApplication, 'insurance_loan.reviewed', [
                'status' => $lockedApplication->status,
                'disbursement_destination' => $lockedApplication->disbursement_destination,
                'insurance_provider_id' => $lockedApplication->insurance_provider_id,
                'repayment_months' => $lockedApplication->repayment_months,
                'monthly_repayment' => $lockedApplication->monthly_repayment,
            ]);

            if ($lockedApplication->status === 'approved' && $lockedApplication->disbursement_destination === 'insurer') {
                $paymentPhone = $lockedApplication->profile()->value('phone')
                    ?? $lockedApplication->user()->value('phone');

                if (! $paymentPhone) {
                    throw ValidationException::withMessages([
                        'status' => __('A customer phone number is required to create the insurer premium payment.'),
                    ]);
                }

                $payment = InsurancePayment::create([
                    'user_id' => $lockedApplication->user_id,
                    'insurance_profile_id' => $lockedApplication->insurance_profile_id,
                    'insurance_policy_id' => $lockedApplication->insurance_policy_id,
                    'insurance_loan_application_id' => $lockedApplication->id,
                    'reference' => (string) Str::uuid(),
                    'phone' => $paymentPhone,
                    'amount' => $lockedApplication->requested_amount,
                    'currency' => 'TZS',
                    'status' => 'pending',
                ]);

                $this->recordAdminActivity($payment, 'insurance_payment.requested_for_loan', [
                    'reference' => $payment->reference,
                    'loan_reference' => $lockedApplication->reference,
                    'amount' => $payment->amount,
                ]);
            }
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

    /**
     * @param  list<string>  $statuses
     * @return array{search: string, status: string}
     */
    private function filters(Request $request, array $statuses): array
    {
        $rules = [
            'search' => ['nullable', 'string', 'max:100'],
        ];

        if ($statuses !== []) {
            $rules['status'] = ['nullable', Rule::in($statuses)];
        }

        $validated = $request->validate($rules);

        return [
            'search' => trim($validated['search'] ?? ''),
            'status' => $validated['status'] ?? '',
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function section(string $section, LengthAwarePaginator $records, array $filters, array $extra = []): View
    {
        return view('admin.section', [
            'section' => $section,
            'records' => $records,
            'filters' => $filters,
        ] + $extra);
    }
}
