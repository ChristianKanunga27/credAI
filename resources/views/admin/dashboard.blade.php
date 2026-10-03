<x-app-layout>
    <x-slot name="header"><div><p class="eyebrow">CredAI</p><h1 class="page-title">{{ __('Insurance administration') }}</h1><p class="page-subtitle">{{ __('Review providers, policies and claims.') }}</p></div></x-slot>
    <div class="dashboard-shell admin-shell">
        @if(session('status'))<div class="workspace-status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="workspace-error">{{ $errors->first() }}</div>@endif
        <section class="metric-grid" aria-label="{{ __('System overview') }}">
            <article class="metric-card"><span class="metric-label">{{ __('Registered users') }}</span><strong>{{ number_format($userCount) }}</strong><span class="metric-note">{{ __('All platform accounts') }}</span></article>
            <article class="metric-card metric-card--accent"><span class="metric-label">{{ __('Insurance providers') }}</span><strong>{{ number_format($providerCount) }}</strong><span class="metric-note">{{ __(':count awaiting review', ['count' => $pendingProviders]) }}</span></article>
            <article class="metric-card"><span class="metric-label">{{ __('Policies') }}</span><strong>{{ number_format($policyCount) }}</strong><span class="metric-note">{{ __(':count active', ['count' => $activePolicies]) }}</span></article>
            <article class="metric-card"><span class="metric-label">{{ __('Claims') }}</span><strong>{{ number_format($claimCount) }}</strong><span class="metric-note">{{ __(':count awaiting review', ['count' => $pendingClaims]) }}</span></article>
        </section>
        <div class="content-grid admin-insurance-grid">
            <section class="surface-card">
                <div class="section-heading"><div><span class="eyebrow">{{ __('Partner review') }}</span><h3>{{ __('Insurance providers') }}</h3></div><span class="status-pill status-pill--soft">{{ $pendingProviders }} {{ __('pending') }}</span></div>
                @forelse($providers as $provider)
                    <div class="admin-row">
                        <div><strong>{{ $provider->organization_name }}</strong><small>{{ $provider->user?->name }} · {{ $provider->license_number ?: __('License not provided') }} · {{ __(ucfirst($provider->status)) }}</small></div>
                        <form method="POST" action="{{ route('admin.insurance-providers.status', $provider) }}" class="admin-status-form">
                            @csrf @method('PATCH')
                            <label class="sr-only" for="provider-status-{{ $provider->id }}">{{ __('Status') }}</label>
                            <select id="provider-status-{{ $provider->id }}" name="status">
                                @foreach(['pending', 'approved', 'suspended', 'rejected'] as $status)
                                    <option value="{{ $status }}" @selected($provider->status === $status)>{{ __(ucfirst($status)) }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="admin-update-button">{{ __('Update') }}</button>
                        </form>
                    </div>
                @empty
                    <p class="empty-copy">{{ __('No insurance providers yet.') }}</p>
                @endforelse
            </section>
            <section class="surface-card">
                <div class="section-heading"><div><span class="eyebrow">{{ __('Coverage') }}</span><h3>{{ __('Policies') }}</h3></div><span class="status-pill status-pill--green">{{ $activePolicies }} {{ __('active') }}</span></div>
                @forelse($policies as $policy)
                    <div class="admin-row">
                        <div><strong>{{ $policy->policy_number }}</strong><small>{{ $policy->user?->name }} · {{ $policy->provider?->organization_name ?? __('Provider unassigned') }} · TZS {{ number_format((float) $policy->premium, 0) }} {{ __('premium') }}</small></div>
                        <form method="POST" action="{{ route('admin.policies.status', $policy) }}" class="admin-status-form">
                            @csrf @method('PATCH')
                            <label class="sr-only" for="policy-status-{{ $policy->id }}">{{ __('Status') }}</label>
                            <select id="policy-status-{{ $policy->id }}" name="status">
                                @foreach(['draft', 'active', 'cancelled', 'expired'] as $status)
                                    <option value="{{ $status }}" @selected($policy->status === $status)>{{ __(ucfirst($status)) }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="admin-update-button">{{ __('Update') }}</button>
                        </form>
                    </div>
                @empty
                    <p class="empty-copy">{{ __('No policies yet.') }}</p>
                @endforelse
            </section>
        </div>
        <section class="surface-card admin-claims">
            <div class="section-heading"><div><span class="eyebrow">{{ __('Service review') }}</span><h3>{{ __('Claims') }}</h3></div><span class="status-pill status-pill--amber">{{ $pendingClaims }} {{ __('pending') }}</span></div>
            @forelse($claims as $claim)
                <div class="admin-row">
                    <div><strong>{{ $claim->claim_number }} · {{ $claim->user?->name }}</strong><small>{{ $claim->policy?->policy_number ?? __('Policy unavailable') }} · {{ ucfirst(str_replace('_', ' ', $claim->claim_type)) }} · TZS {{ number_format((float) $claim->claim_amount, 0) }} · {{ __(ucfirst(str_replace('_', ' ', $claim->status))) }}</small></div>
                    <form method="POST" action="{{ route('admin.claims.status', $claim) }}" class="admin-status-form">
                        @csrf @method('PATCH')
                        <label class="sr-only" for="claim-status-{{ $claim->id }}">{{ __('Status') }}</label>
                        <select id="claim-status-{{ $claim->id }}" name="status">
                            @foreach(['submitted', 'under_review', 'approved', 'rejected', 'paid'] as $status)
                                <option value="{{ $status }}" @selected($claim->status === $status)>{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="admin-update-button">{{ __('Update') }}</button>
                    </form>
                </div>
            @empty
                <p class="empty-copy">{{ __('No claims yet.') }}</p>
            @endforelse
        </section>
        <div class="content-grid admin-insurance-grid">
            <section class="surface-card">
                <div class="section-heading"><div><span class="eyebrow">{{ __('Mobile money') }}</span><h3>{{ __('Payment requests') }}</h3></div><span class="status-pill status-pill--amber">{{ $pendingPayments }} {{ __('pending') }}</span></div>
                @forelse($payments as $payment)
                    <div class="admin-row">
                        <div><strong>{{ $payment->user?->name }} · TZS {{ number_format((float) $payment->amount, 0) }}</strong><small>{{ $payment->mobile_money_provider ? __(ucfirst(str_replace('_', ' ', $payment->mobile_money_provider))) : __('Provider not configured') }} · {{ $payment->phone }} · {{ $payment->reference }} · {{ __(ucfirst($payment->status)) }}</small></div>
                        @if(in_array($payment->status, ['pending', 'processing'], true))
                            <form method="POST" action="{{ route('admin.payments.status', $payment) }}" class="admin-status-form">
                                @csrf @method('PATCH')
                                <label class="sr-only" for="payment-status-{{ $payment->id }}">{{ __('Status') }}</label>
                                <select id="payment-status-{{ $payment->id }}" name="status"><option value="processing">{{ __('Processing') }}</option><option value="paid">{{ __('Paid') }}</option><option value="failed">{{ __('Failed') }}</option><option value="cancelled">{{ __('Cancelled') }}</option></select>
                                <input name="provider_reference" aria-label="{{ __('Provider reference') }}" placeholder="{{ __('Provider reference') }}" maxlength="255">
                                <button type="submit" class="admin-update-button">{{ __('Update') }}</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="empty-copy">{{ __('No payment requests yet.') }}</p>
                @endforelse
            </section>
            <section class="surface-card">
                <div class="section-heading"><div><span class="eyebrow">{{ __('Credit review') }}</span><h3>{{ __('Loan applications') }}</h3></div><span class="status-pill status-pill--amber">{{ $pendingLoans }} {{ __('pending') }}</span></div>
                @forelse($loanApplications as $application)
                    <div class="admin-row admin-loan-row">
                        <div><strong>{{ $application->user?->name }} · TZS {{ number_format((float) $application->requested_amount, 0) }}</strong><small>{{ $application->reference }} · {{ $application->disbursement_destination === 'insurer' ? __('Pay the insurer directly') : __('Disburse to customer') }} · {{ $application->disbursement_phone ?: __('No disbursement phone') }} · {{ $application->balance_verified_at_application ? __('Balance verified') : __('Balance not verified') }}</small></div>
                        @if(in_array($application->status, ['submitted', 'under_review'], true))
                            <form method="POST" action="{{ route('admin.loans.review', $application) }}" class="admin-loan-form">
                                @csrf @method('PATCH')
                                <label class="sr-only" for="loan-status-{{ $application->id }}">{{ __('Status') }}</label>
                                <select id="loan-status-{{ $application->id }}" name="status"><option value="under_review">{{ __('Under review') }}</option><option value="approved">{{ __('Approved') }}</option><option value="rejected">{{ __('Rejected') }}</option></select>
                                <select name="insurance_provider_id" aria-label="{{ __('Approved loan provider') }}"><option value="">{{ __('Select approved provider') }}</option>@foreach($approvedProviders as $approvedProvider)<option value="{{ $approvedProvider->id }}">{{ $approvedProvider->organization_name }}</option>@endforeach</select>
                                <input name="repayment_months" type="number" min="1" max="60" aria-label="{{ __('Repayment months') }}" placeholder="{{ __('Months') }}">
                                <input name="monthly_repayment" type="number" min="1" step="1" aria-label="{{ __('Monthly repayment (TZS)') }}" placeholder="{{ __('Monthly TZS') }}">
                                <input name="decision_notes" aria-label="{{ __('Decision notes') }}" placeholder="{{ __('Decision notes') }}" maxlength="2000">
                                <button type="submit" class="admin-update-button">{{ __('Review') }}</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="empty-copy">{{ __('No loan applications yet.') }}</p>
                @endforelse
            </section>
        </div>
        <section class="surface-card admin-claims">
            <div class="section-heading"><div><span class="eyebrow">{{ __('Audit log') }}</span><h3>{{ __('Recent activity') }}</h3></div></div>
            @forelse($activities as $activity)
                <div class="admin-row"><div><strong>{{ $activity->actor_name ?? __('Deleted account') }} · {{ __(ucfirst(str_replace(['.', '_'], ' ', $activity->event))) }}</strong><small>{{ $activity->created_at }} · {{ $activity->auditable_type }} #{{ $activity->auditable_id }}</small></div></div>
            @empty
                <p class="empty-copy">{{ __('No activity recorded yet.') }}</p>
            @endforelse
        </section>
    </div>
</x-app-layout>
