@php
    $sectionTitles = [
        'providers' => __('Insurance providers'),
        'policies' => __('Insurance policies'),
        'claims' => __('Claims'),
        'payments' => __('Payment requests'),
        'loans' => __('Loan applications'),
        'activity' => __('Activity log'),
    ];
    $sectionStatuses = [
        'providers' => ['pending', 'approved', 'suspended', 'rejected'],
        'policies' => ['draft', 'active', 'cancelled', 'expired'],
        'claims' => ['submitted', 'under_review', 'approved', 'rejected', 'paid'],
        'payments' => ['pending', 'processing', 'paid', 'failed', 'cancelled'],
        'loans' => ['submitted', 'under_review', 'approved', 'rejected'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="eyebrow">{{ __('CredHealth administration') }}</p>
            <h1 class="page-title">{{ $sectionTitles[$section] }}</h1>
            <p class="page-subtitle">{{ __('Search, filter, and review :count records.', ['count' => number_format($records->total())]) }}</p>
        </div>
    </x-slot>

    <div class="dashboard-shell admin-shell">
        @if(session('status'))<div class="workspace-status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="workspace-error">{{ $errors->first() }}</div>@endif

        <section class="surface-card admin-records-card">
            <form class="admin-filters" method="GET" action="{{ request()->url() }}">
                <div class="form-field admin-search-field">
                    <label for="admin-search">{{ __('Search :section', ['section' => strtolower($sectionTitles[$section])]) }}</label>
                    <input id="admin-search" name="search" type="search" value="{{ $filters['search'] }}" maxlength="100" placeholder="{{ __('Enter a name or reference') }}">
                </div>
                @if(isset($sectionStatuses[$section]))
                    <div class="form-field admin-status-filter">
                        <label for="admin-status-filter">{{ __('Status') }}</label>
                        <select id="admin-status-filter" name="status">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach($sectionStatuses[$section] as $status)
                                <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="admin-filter-actions">
                    <button class="admin-update-button" type="submit">{{ __('Apply filters') }}</button>
                    <a class="form-cancel" href="{{ request()->url() }}">{{ __('Clear') }}</a>
                </div>
            </form>

            <div class="admin-record-list">
                @forelse($records as $record)
                    <article class="admin-record">
                        @if($section === 'providers')
                            <div class="admin-record-main">
                                <strong>{{ $record->organization_name }}</strong>
                                <small>{{ __(ucfirst($record->provider_type)) }} · {{ $record->user?->name }} · {{ $record->user?->email }} · {{ $record->license_number ?: __('License not provided') }}</small>
                                <span class="status-pill status-pill--soft">{{ __(ucfirst($record->status)) }}</span>
                            </div>
                            <form method="POST" action="{{ route('admin.insurance-providers.status', $record) }}" class="admin-record-action">
                                @csrf @method('PATCH')
                                <label class="sr-only" for="provider-status-{{ $record->id }}">{{ __('Provider status') }}</label>
                                <select id="provider-status-{{ $record->id }}" name="status">
                                    @foreach($sectionStatuses[$section] as $status)
                                        <option value="{{ $status }}" @selected($record->status === $status)>{{ __(ucfirst($status)) }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="admin-update-button">{{ __('Update') }}</button>
                            </form>
                        @elseif($section === 'policies')
                            <div class="admin-record-main">
                                <strong>{{ $record->policy_number }}</strong>
                                <small>{{ $record->user?->name }} · {{ $record->provider?->organization_name ?? __('Provider unassigned') }} · {{ __(ucfirst(str_replace('_', ' ', $record->coverage_goal))) }}</small>
                                <small>{{ __('Premium') }}: TZS {{ number_format((float) $record->premium, 0) }} · {{ __('Coverage') }}: TZS {{ number_format((float) $record->coverage_amount, 0) }}</small>
                                <span class="status-pill status-pill--soft">{{ __(ucfirst($record->status)) }}</span>
                            </div>
                            <form method="POST" action="{{ route('admin.policies.status', $record) }}" class="admin-record-action">
                                @csrf @method('PATCH')
                                <label class="sr-only" for="policy-status-{{ $record->id }}">{{ __('Policy status') }}</label>
                                <select id="policy-status-{{ $record->id }}" name="status">
                                    @foreach($sectionStatuses[$section] as $status)
                                        <option value="{{ $status }}" @selected($record->status === $status)>{{ __(ucfirst($status)) }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="admin-update-button">{{ __('Update') }}</button>
                            </form>
                        @elseif($section === 'claims')
                            <div class="admin-record-main">
                                <strong>{{ $record->claim_number }} · {{ $record->user?->name }}</strong>
                                <small>{{ $record->policy?->policy_number ?? __('Policy unavailable') }} · {{ __(ucfirst(str_replace('_', ' ', $record->claim_type))) }} · TZS {{ number_format((float) $record->claim_amount, 0) }}</small>
                                @if($record->description)<small>{{ $record->description }}</small>@endif
                                <span class="status-pill status-pill--soft">{{ __(ucfirst(str_replace('_', ' ', $record->status))) }}</span>
                            </div>
                            <form method="POST" action="{{ route('admin.claims.status', $record) }}" class="admin-record-action">
                                @csrf @method('PATCH')
                                <label class="sr-only" for="claim-status-{{ $record->id }}">{{ __('Claim status') }}</label>
                                <select id="claim-status-{{ $record->id }}" name="status">
                                    @foreach($sectionStatuses[$section] as $status)
                                        <option value="{{ $status }}" @selected($record->status === $status)>{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="admin-update-button">{{ __('Update') }}</button>
                            </form>
                        @elseif($section === 'payments')
                            <div class="admin-record-main">
                                <strong>{{ $record->user?->name }} · TZS {{ number_format((float) $record->amount, 0) }}</strong>
                                <small>{{ $record->reference }} · {{ $record->provider_reference ?: __('Provider reference not recorded') }} · {{ $record->phone }}</small>
                                <small>{{ $record->mobile_money_provider ? __(ucfirst(str_replace('_', ' ', $record->mobile_money_provider))) : ($record->loanApplication?->provider?->organization_name ?? __('ClickPesa')) }} · {{ $record->policy?->policy_number ?? __('No policy linked') }}</small>
                                @if($record->clickpesa_control_number)
                                    <small>{{ __('BillPay control number') }}: {{ $record->clickpesa_control_number }}</small>
                                @endif
                                @if($record->loanApplication)
                                    <small>{{ __('Premium financing loan') }} · {{ $record->loanApplication->reference }} · {{ $record->loanApplication->provider?->organization_name ?? __('Lender unassigned') }}</small>
                                @endif
                                @if($record->providerService)
                                    <small>{{ __('Provider service') }} · {{ $record->providerService->name }} · {{ $record->providerService->provider?->organization_name }}</small>
                                @endif
                                @if($record->status === 'paid')
                                    <small>{{ __('Confirmed :date via :source by :admin', [
                                        'date' => $record->paid_at?->format('Y-m-d H:i') ?? __('date unavailable'),
                                        'source' => __(ucfirst($record->confirmation_source ?? 'unknown')),
                                        'admin' => $record->confirmedBy?->name ?? __('payment provider'),
                                    ]) }}</small>
                                @endif
                                <span class="status-pill {{ $record->status === 'paid' ? 'status-pill--green' : ($record->status === 'pending' || $record->status === 'processing' ? 'status-pill--amber' : 'status-pill--soft') }}">{{ __(ucfirst($record->status)) }}</span>
                            </div>
                            @if(in_array($record->status, ['pending', 'processing'], true))
                                <form method="POST" action="{{ route('admin.payments.status', $record) }}" class="admin-record-action admin-payment-action" data-payment-review>
                                    @csrf @method('PATCH')
                                    <label class="sr-only" for="payment-status-{{ $record->id }}">{{ __('Payment status') }}</label>
                                    <select id="payment-status-{{ $record->id }}" name="status" data-payment-status>
                                        @foreach(['processing', 'paid', 'failed', 'cancelled'] as $status)
                                            <option value="{{ $status }}" @selected($record->status === $status)>{{ __(ucfirst($status)) }}</option>
                                        @endforeach
                                    </select>
                                    <input name="provider_reference" maxlength="255" value="{{ old('provider_reference') }}" placeholder="{{ __('Verified receipt / transaction reference') }}" aria-label="{{ __('Verified receipt / transaction reference') }}" data-payment-reference>
                                    <small>{{ __('Only mark paid after checking the provider settlement or receipt.') }}</small>
                                    <button type="submit" class="admin-update-button">{{ __('Update') }}</button>
                                    @error('provider_reference')<small class="workspace-error">{{ $message }}</small>@enderror
                                </form>
                            @endif
                        @elseif($section === 'loans')
                            <div class="admin-record-main">
                                <strong>{{ $record->user?->name }} · TZS {{ number_format((float) $record->requested_amount, 0) }}</strong>
                                <small>{{ $record->reference }} · {{ $record->disbursement_destination === 'insurer' ? __('Pay the insurer directly') : __('Disburse to customer') }} · {{ $record->disbursement_phone ?: __('No disbursement phone') }}</small>
                                <small>{{ $record->balance_verified_at_application ? __('Balance verified') : __('Balance not verified') }} · {{ $record->provider?->organization_name ?? __('No provider assigned') }}</small>
                                @if($record->premiumPayment)
                                    <small>{{ __('Insurer premium payment') }} · {{ $record->premiumPayment->reference }} · {{ __(ucfirst($record->premiumPayment->status)) }}</small>
                                @endif
                                @if($record->decision_notes)<small>{{ $record->decision_notes }}</small>@endif
                                <span class="status-pill status-pill--soft">{{ __(ucfirst(str_replace('_', ' ', $record->status))) }}</span>
                            </div>
                            @if(in_array($record->status, ['submitted', 'under_review'], true))
                                <form method="POST" action="{{ route('admin.loans.review', $record) }}" class="admin-record-action admin-loan-review" data-loan-review>
                                    @csrf @method('PATCH')
                                    <label class="sr-only" for="loan-status-{{ $record->id }}">{{ __('Loan status') }}</label>
                                    <select id="loan-status-{{ $record->id }}" name="status" data-loan-status>
                                        @foreach(['under_review', 'approved', 'rejected'] as $status)
                                            <option value="{{ $status }}" @selected($record->status === $status)>{{ __(ucfirst(str_replace('_', ' ', $status))) }}</option>
                                        @endforeach
                                    </select>
                                    <select name="insurance_provider_id" aria-label="{{ __('Approved loan provider') }}" data-loan-provider>
                                        <option value="">{{ __('Select approved provider') }}</option>
                                        @foreach($approvedProviders as $provider)
                                            <option value="{{ $provider->id }}" @selected($record->insurance_provider_id === $provider->id)>{{ $provider->organization_name }}</option>
                                        @endforeach
                                    </select>
                                    <input name="repayment_months" type="number" min="1" max="60" value="{{ old('repayment_months') }}" aria-label="{{ __('Repayment months') }}" placeholder="{{ __('Months') }}" data-loan-months>
                                    <input name="monthly_repayment" type="number" min="1" step="1" value="{{ old('monthly_repayment') }}" aria-label="{{ __('Monthly repayment (TZS)') }}" placeholder="{{ __('Monthly TZS') }}" data-loan-repayment>
                                    @if(!$record->balance_verified_at_application)
                                        <label><input type="checkbox" name="verify_balance" value="1" data-loan-balance-verification> {{ __('I verified the customer mobile-money balance using an external source.') }}</label>
                                        <input name="balance_verification_reference" maxlength="255" value="{{ old('balance_verification_reference') }}" aria-label="{{ __('Balance verification source or reference') }}" placeholder="{{ __('Verification source / reference') }}" data-loan-balance-reference>
                                    @endif
                                    <input name="decision_notes" maxlength="2000" value="{{ old('decision_notes') }}" aria-label="{{ __('Decision notes') }}" placeholder="{{ __('Decision notes') }}">
                                    <button type="submit" class="admin-update-button">{{ __('Save review') }}</button>
                                    @if(!$record->balance_verified_at_application)<small>{{ __('Approval requires verified mobile-money balance.') }}</small>@endif
                                </form>
                            @endif
                        @else
                            <div class="admin-record-main">
                                <strong>{{ $record->actor_name ?? __('Deleted account') }} · {{ __(ucfirst(str_replace(['.', '_'], ' ', $record->event))) }}</strong>
                                <small>{{ $record->created_at }} · {{ $record->auditable_type }} #{{ $record->auditable_id }}</small>
                                @if($record->metadata)<small>{{ $record->metadata }}</small>@endif
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="empty-state">
                        <strong>{{ __('No :section found', ['section' => strtolower($sectionTitles[$section])]) }}</strong>
                        <p>{{ __('Try adjusting your search or status filter.') }}</p>
                    </div>
                @endforelse
            </div>

            @if($records->hasPages())
                <div class="admin-pagination">{{ $records->links() }}</div>
            @endif
        </section>
    </div>

    @if(in_array($section, ['payments', 'loans'], true))
        <script>
            document.querySelectorAll('[data-payment-review]').forEach((form) => {
                const status = form.querySelector('[data-payment-status]');
                const reference = form.querySelector('[data-payment-reference]');
                const syncReference = () => { reference.required = status.value === 'paid'; };
                status.addEventListener('change', syncReference);
                syncReference();
            });

            document.querySelectorAll('[data-loan-review]').forEach((form) => {
                const status = form.querySelector('[data-loan-status]');
                const provider = form.querySelector('[data-loan-provider]');
                const months = form.querySelector('[data-loan-months]');
                const repayment = form.querySelector('[data-loan-repayment]');
                const balanceVerification = form.querySelector('[data-loan-balance-verification]');
                const balanceReference = form.querySelector('[data-loan-balance-reference]');
                const syncApprovalFields = () => {
                    const approving = status.value === 'approved';
                    provider.required = approving;
                    months.required = approving;
                    repayment.required = approving;
                    if (balanceVerification) {
                        balanceVerification.disabled = !approving;
                        if (!approving) {
                            balanceVerification.checked = false;
                        }
                        balanceVerification.required = approving;
                    }
                    if (balanceReference) {
                        balanceReference.disabled = !approving;
                        balanceReference.required = Boolean(balanceVerification?.checked);
                    }
                };
                status.addEventListener('change', syncApprovalFields);
                balanceVerification?.addEventListener('change', syncApprovalFields);
                syncApprovalFields();
            });
        </script>
    @endif
</x-app-layout>
