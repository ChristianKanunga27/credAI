<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="eyebrow">CredHealth</p>
            <h1 class="page-title">{{ __('Administration overview') }}</h1>
            <p class="page-subtitle">{{ __('Monitor the platform and open a dedicated workspace for each area.') }}</p>
        </div>
    </x-slot>

    <div class="dashboard-shell admin-shell">
        <section class="metric-grid" id="admin-overview" aria-label="{{ __('System overview') }}">
            <a class="metric-card admin-overview-card" href="{{ route('admin.providers') }}">
                <span class="metric-label">{{ __('Insurance providers') }}</span>
                <strong>{{ number_format($providerCount) }}</strong>
                <span class="metric-note">{{ __(':count awaiting review', ['count' => $pendingProviders]) }}</span>
            </a>
            <a class="metric-card metric-card--accent admin-overview-card" href="{{ route('admin.policies') }}">
                <span class="metric-label">{{ __('Policies') }}</span>
                <strong>{{ number_format($policyCount) }}</strong>
                <span class="metric-note">{{ __(':count active', ['count' => $activePolicies]) }}</span>
            </a>
            <a class="metric-card admin-overview-card" href="{{ route('admin.claims') }}">
                <span class="metric-label">{{ __('Claims') }}</span>
                <strong>{{ number_format($claimCount) }}</strong>
                <span class="metric-note">{{ __(':count awaiting review', ['count' => $pendingClaims]) }}</span>
            </a>
            <a class="metric-card metric-card--accent admin-overview-card" href="{{ route('admin.payments') }}">
                <span class="metric-label">{{ __('Payment requests') }}</span>
                <strong>{{ number_format($paymentCount) }}</strong>
                <span class="metric-note">{{ __(':pending pending · :paid successfully confirmed', ['pending' => $pendingPayments, 'paid' => $successfulPayments]) }}</span>
            </a>
            <a class="metric-card admin-overview-card" href="{{ route('admin.loans') }}">
                <span class="metric-label">{{ __('Loan applications') }}</span>
                <strong>{{ number_format($loanCount) }}</strong>
                <span class="metric-note">{{ __(':count pending', ['count' => $pendingLoans]) }}</span>
            </a>
            <article class="metric-card">
                <span class="metric-label">{{ __('Registered users') }}</span>
                <strong>{{ number_format($userCount) }}</strong>
                <span class="metric-note">{{ __('All platform accounts') }}</span>
            </article>
        </section>

        <section class="surface-card admin-overview-shortcuts" aria-labelledby="admin-shortcuts-heading">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">{{ __('Administration') }}</span>
                    <h3 id="admin-shortcuts-heading">{{ __('Go to a workspace') }}</h3>
                </div>
            </div>
            <nav class="admin-shortcut-grid" aria-label="{{ __('Admin workspaces') }}">
                <a href="{{ route('admin.providers') }}">{{ __('Review providers') }} <span aria-hidden="true">&rarr;</span></a>
                <a href="{{ route('admin.policies') }}">{{ __('Manage policies') }} <span aria-hidden="true">&rarr;</span></a>
                <a href="{{ route('admin.claims') }}">{{ __('Review claims') }} <span aria-hidden="true">&rarr;</span></a>
                <a href="{{ route('admin.payments') }}">{{ __('Process payments') }} <span aria-hidden="true">&rarr;</span></a>
                <a href="{{ route('admin.loans') }}">{{ __('Review loan applications') }} <span aria-hidden="true">&rarr;</span></a>
                <a href="{{ route('admin.activity') }}">{{ __('View activity log') }} <span aria-hidden="true">&rarr;</span></a>
            </nav>
        </section>
    </div>
</x-app-layout>
