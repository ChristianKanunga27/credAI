<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="eyebrow">
                    {{ $role === 'insurer' ? 'Insurance workspace' : ($role === 'admin' ? 'Admin workspace' : 'Personal workspace') }}
                </p>
                <h1 class="page-title">Good morning, {{ Str::before(auth()->user()->name, ' ') }}.</h1>
                <p class="page-subtitle">
                    {{ $role === 'insurer'
                        ? 'Review policy demand, underwriting risk, and AI recommendations in one clear view.'
                        : ($role === 'admin'
                            ? 'Monitor the entire CredHealth protection network and customer health across the platform.'
                            : 'Track your mobile balance, recommended cover, and next-best protection options.') }}
                </p>
            </div>
            <span class="status-pill status-pill--soft">{{ $roleLabel }}</span>
        </div>
    </x-slot>

    <div class="dashboard-shell">
        @if(session('status'))<div class="workspace-status">{{ session('status') }}</div>@endif
        @if(session('error'))<div class="workspace-error">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="workspace-error">{{ $errors->first() }}</div>@endif
        @if($role === 'individual' && $activePolicy)
            <section class="surface-card mb-6">
                <div class="section-heading">
                    <div><span class="eyebrow">{{ __('Payment confirmed · coverage active') }}</span><h3>{{ __('Your insurance card is ready') }}</h3><p class="empty-copy">{{ __('Policy :number · TZS :amount annual cover', ['number' => $activePolicy->policy_number, 'amount' => number_format((float) $activePolicy->coverage_amount, 0)]) }}</p></div>
                    <a href="{{ route('insurance.card') }}" class="button button--green">{{ __('View my card') }}</a>
                </div>
            </section>
        @endif
        @if($role === 'individual')
            <section class="surface-card mb-6">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">{{ __('Available mobile-money balance') }}</span>
                        <h3>{{ number_format($simBalance, 2) }} TZS</h3>
                        <p class="empty-copy">{{ $mobileMoneyTransactionCount > 0
                            ? __('Based on :count verified transactions, less pending payment and loan requests.', ['count' => $mobileMoneyTransactionCount])
                            : __('Generate a direct-payment quote now, or sync your mobile-money transaction history before applying for a loan.') }}</p>
                    </div>
                    <form method="POST" action="{{ route('transactions.sync') }}">
                        @csrf
                        <button type="submit" class="button button--green">{{ __('Sync mobile-money transactions') }}</button>
                    </form>
                </div>
            </section>
            <section class="surface-card mb-6">
                <div class="section-heading">
                    <div><span class="eyebrow">{{ __('Verified transaction history') }}</span><h3>{{ __('Recent mobile-money activity') }}</h3></div>
                    <a class="form-cancel" href="{{ route('transactions.mobile-money.index') }}">{{ __('View full history') }}</a>
                </div>
                @forelse($mobileMoneyTransactions as $transaction)
                    <div class="admin-row">
                        <div><strong>{{ $transaction->provider ?: __('Mobile money') }} · {{ $transaction->reference }}</strong><small>{{ $transaction->occurred_at->format('Y-m-d H:i') }} · {{ __(ucfirst($transaction->transaction_type)) }}</small></div>
                        <strong class="{{ $transaction->transaction_type === 'credit' ? 'text-emerald-700' : 'text-slate-700' }}">{{ $transaction->transaction_type === 'debit' ? '−' : '+' }} TZS {{ number_format((float) $transaction->amount, 2) }}</strong>
                    </div>
                @empty
                    <p class="empty-copy">{{ __('No verified mobile-money transactions have been synced yet.') }}</p>
                @endforelse
            </section>
        @endif

        <section class="hero-panel">
            <div class="hero-copy">
                <span class="eyebrow eyebrow--light">
                    {{ $role === 'insurer' ? 'CredHealth underwriting desk' : ($role === 'admin' ? 'CredHealth command centre' : 'CredHealth personal protection desk') }}
                </span>
                <h2>
                    {{ $role === 'insurer'
                        ? 'Offer fairer protection with better risk insight.'
                        : ($role === 'admin'
                            ? 'See the complete protection ecosystem in real time.'
                            : 'Turn your mobile-money balance into smarter protection.') }}
                </h2>
                <p>
                    {{ $role === 'insurer'
                        ? 'Scan submitted risk profiles, model premium strength, and let AI support smarter underwriting decisions.'
                        : ($role === 'admin'
                            ? 'Manage users, policies, and protection coverage quality across a single control layer.'
                            : 'Your SIM balance and health profile help the system suggest a realistic, affordable cover and premium plan.') }}
                </p>
                @if($role === 'individual')
                    <a href="{{ route('insurance.quote') }}" class="button button--light">Get my quote <span aria-hidden="true">&rarr;</span></a>
                    <a href="{{ route('insurance.card') }}" class="button button--light">{{ __('My insurance card') }}</a>
                    <a href="{{ route('insurance.services.index') }}" class="button button--light">{{ __('Pay a provider service') }}</a>
                @elseif($role === 'insurer')
                    <a href="#insights" class="button button--light">Review underwriting <span aria-hidden="true">&rarr;</span></a>
                @else
                    <a href="#insights" class="button button--light">Open command centre <span aria-hidden="true">&rarr;</span></a>
                @endif
            </div>
            <div class="hero-orbit" aria-hidden="true">
                <div class="orbit-ring"></div>
                <div class="orbit-core">
                    @if($role === 'individual')
                        {{ number_format($simBalance / 1000, 0) }}<small>K</small>
                    @else
                        24<small>h</small>
                    @endif
                    <span>                    {{ $role === 'individual' ? __('Verified balance') : 'smart review' }}</span>
                </div>
                <div class="orbit-tag orbit-tag--top">{{ $role === 'individual' ? 'AI recommended' : 'Risk insight' }}</div>
                <div class="orbit-tag orbit-tag--bottom">{{ $role === 'individual' ? 'Affordable cover' : 'Policy health' }}</div>
            </div>
        </section>

        <section class="metric-grid" aria-label="Protection overview">
            @if($role === 'individual')
                <article class="metric-card"><span class="metric-label">{{ __('Available transaction balance') }}</span><strong>{{ number_format($simBalance, 0) }} <small>TZS</small></strong><span class="metric-note">{{ __('Verified transactions less pending requests') }}</span></article>
                <article class="metric-card metric-card--accent"><span class="metric-label">{{ __('Annual premium') }}</span><strong>{{ number_format((float) config('insurance.annual_premium', 50400), 0) }}<small>TZS</small></strong><span class="metric-note">{{ __('CredHealth yearly plan') }}</span></article>
                <article class="metric-card"><span class="metric-label">{{ __('Annual coverage value') }}</span><strong>{{ number_format((float) config('insurance.annual_coverage', 100000), 0) }} <small>TZS</small></strong><span class="metric-note">{{ $quote['recommended_plan'] }}</span></article>
                <article class="metric-card"><span class="metric-label">Risk level</span><strong>{{ ucfirst($quote['risk_level']) }}</strong><span class="metric-note">AI-adjusted protection fit</span></article>
            @endif
        </section>

        <div class="content-grid" id="insights">
            <section class="surface-card">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">{{ $role === 'insurer' ? 'Coverage pipeline' : 'Protection readiness' }}</span>
                        <h3>{{ $role === 'insurer' ? 'Underwriting snapshot' : 'Your protection summary' }}</h3>
                    </div>
                    <span class="status-pill {{ $role === 'individual' ? 'status-pill--green' : 'status-pill--amber' }}">
                        {{ $role === 'individual' ? 'Ready' : 'Needs review' }}
                    </span>
                </div>

                @if($role === 'individual')
                    <div class="readiness-track"><span style="width: {{ min(($simBalance / 500000) * 100, 100) }}%"></span></div>
                    <div class="readiness-labels"><span>0%</span><strong>{{ number_format(min(($simBalance / 500000) * 100, 100), 1) }}% fit</strong><span>100%</span></div>
                    <div class="check-list">
                        <div class="check-row check-row--done"><span class="check-icon">✓</span><div><strong>Mobile balance check</strong><small>SIM account data is connected and reviewed.</small></div></div>
                        <div class="check-row check-row--done"><span class="check-icon">✓</span><div><strong>AI profile review</strong><small>Your risk profile is matched to the right cover tier.</small></div></div>
                        <div class="check-row"><span class="check-icon">3</span><div><strong>Complete your policy choice</strong><small>Choose a plan and confirm your monthly premium.</small></div></div>
                    </div>
                    <a href="{{ route('insurance.quote') }}" class="button button--green">Review my coverage <span aria-hidden="true">&rarr;</span></a>
                @elseif($role === 'insurer')
                    <div class="check-list">
                        <div class="check-row check-row--done"><span class="check-icon">✓</span><div><strong>Risk screening</strong><small>36 applicants match the preferred eligibility band.</small></div></div>
                        <div class="check-row check-row--done"><span class="check-icon">✓</span><div><strong>AI underwriting notes</strong><small>12 cases need manual validation before approval.</small></div></div>
                        <div class="check-row"><span class="check-icon">3</span><div><strong>Approval queue</strong><small>Prepare policy docs for the next 9 customers.</small></div></div>
                    </div>
                    <a href="{{ route('insurance.quote') }}" class="button button--green">Open underwriting view <span aria-hidden="true">&rarr;</span></a>
                @else
                    <div class="check-list">
                        <div class="check-row check-row--done"><span class="check-icon">✓</span><div><strong>Platform health</strong><small>Core services and AI recommendations are online.</small></div></div>
                        <div class="check-row check-row--done"><span class="check-icon">✓</span><div><strong>Provider oversight</strong><small>All active insurers are audited for SLA compliance.</small></div></div>
                        <div class="check-row"><span class="check-icon">3</span><div><strong>Issue review</strong><small>Four operational checks require your response today.</small></div></div>
                    </div>
                    <a href="{{ route('admin.dashboard') }}" class="button button--green">Open control panel <span aria-hidden="true">&rarr;</span></a>
                @endif
            </section>

            <section class="surface-card activity-card">
                <div class="section-heading">
                    <div>
                    <span class="eyebrow">CredAI copilot</span>
                        <h3>{{ $role === 'insurer' ? 'Recommendation engine' : ($role === 'admin' ? 'Operational intelligence' : 'Your insurance coach') }}</h3>
                    </div>
                </div>

                <div class="ai-panel">
                    <span class="ai-badge">{{ $aiEnabled ? 'AI' : __('Rules-based preview') }}</span>
                    <p>{{ $quote['ai_summary'] }}</p>
                </div>

                <ul class="ai-list">
                    <li><strong>Plan</strong><span>{{ $quote['recommended_plan'] }}</span></li>
                    <li><strong>{{ __('Annual premium') }}</strong><span>{{ number_format((float) config('insurance.annual_premium', 50400), 0) }} TZS / year</span></li>
                    <li><strong>{{ __('Annual coverage') }}</strong><span>{{ number_format((float) config('insurance.annual_coverage', 100000), 0) }} TZS</span></li>
                    <li><strong>Goal</strong><span>{{ $quote['goal_label'] }}</span></li>
                </ul>
            </section>
        </div>

        <section class="partner-strip">
            <div>
                <span class="eyebrow">One connected system</span>
                <h3>Customer, insurer, and admin teams all work from the same protection truth.</h3>
            </div>
            <div class="partner-steps">
                <span><b>01</b> Normal user</span>
                <i>&rarr;</i>
                <span><b>02</b> Insurance provider</span>
                <i>&rarr;</i>
                <span><b>03</b> Admin oversight</span>
            </div>
        </section>

        @if($role === 'individual')
            <div class="content-grid mt-6">
                <section class="surface-card">
                    <div class="section-heading"><div><span class="eyebrow">{{ __('Mobile money') }}</span><h3>{{ __('Payment requests') }}</h3></div></div>
                    @forelse($payments as $payment)
                        <div class="admin-row">
                            <div>
                                <strong>{{ $payment->reference }}</strong>
                                <small>TZS {{ number_format((float) $payment->amount, 0) }} · {{ $payment->providerService
                                    ? __(':service at :provider', ['service' => $payment->providerService->name, 'provider' => $payment->providerService->provider?->organization_name])
                                    : ($payment->loanApplication ? __('Premium financing') : __('Insurance premium')) }} · {{ $payment->mobile_money_provider ? __(ucfirst(str_replace('_', ' ', $payment->mobile_money_provider))) : __('ClickPesa') }}</small>
                                @if($payment->clickpesa_control_number && $payment->status !== 'paid')
                                    <small><strong>{{ __('Control number') }}: {{ $payment->clickpesa_control_number }}</strong></small>
                                    <small>{{ __('Use Pay Bill / Lipa kwa Simu in your mobile-money app, enter this number and the exact amount, then return here after payment.') }}</small>
                                @endif
                                @if($payment->status === 'paid')
                                    <small>{{ __('Paid :date · Receipt :reference', [
                                        'date' => $payment->paid_at?->format('Y-m-d H:i') ?? __('date unavailable'),
                                        'reference' => $payment->provider_reference,
                                    ]) }}</small>
                                @elseif(in_array($payment->status, ['pending', 'processing'], true))
                                    <small>{{ __('Waiting for payment confirmation. No payment is marked successful until confirmed.') }}</small>
                                @endif
                            </div>
                            <span class="status-pill {{ $payment->status === 'paid' ? 'status-pill--green' : (in_array($payment->status, ['pending', 'processing'], true) ? 'status-pill--amber' : 'status-pill--soft') }}">{{ __(ucfirst($payment->status)) }}</span>
                        </div>
                    @empty
                        <p class="empty-copy">{{ __('No payment requests yet.') }}</p>
                    @endforelse
                </section>
                <section class="surface-card">
                    <div class="section-heading"><div><span class="eyebrow">{{ __('Credit review') }}</span><h3>{{ __('Loan applications') }}</h3></div></div>
                    @forelse($loanApplications as $application)
                        <div class="admin-row">
                            <div>
                                <strong>{{ $application->reference }}</strong>
                                <small>TZS {{ number_format((float) $application->requested_amount, 0) }} · {{ $application->disbursement_destination === 'insurer' ? __('Pay the insurer directly') : __('Disburse to me') }}</small>
                                @if($application->premiumPayment)
                                    <small>{{ __('Premium payment') }} · {{ __(ucfirst($application->premiumPayment->status)) }} · {{ $application->premiumPayment->reference }}</small>
                                @endif
                            </div>
                            <span class="status-pill {{ $application->status === 'approved' ? 'status-pill--green' : (in_array($application->status, ['submitted', 'under_review'], true) ? 'status-pill--amber' : 'status-pill--soft') }}">{{ __(ucfirst(str_replace('_', ' ', $application->status))) }}</span>
                        </div>
                    @empty
                        <p class="empty-copy">{{ __('No loan applications yet.') }}</p>
                    @endforelse
                </section>
            </div>
        @endif
    </div>
</x-app-layout>
