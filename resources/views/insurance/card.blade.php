<x-app-layout>
    <x-slot name="header">
        <p class="eyebrow">{{ __('CredHealth membership') }}</p>
        <h1 class="page-title">{{ __('My insurance card') }}</h1>
        <p class="page-subtitle">{{ __('Your active coverage and approved hospital network.') }}</p>
    </x-slot>

    <div class="dashboard-shell">
        @if($policy)
            <section class="hero-panel">
                <div class="hero-copy">
                    <span class="eyebrow eyebrow--light">{{ __('Active annual cover') }}</span>
                    <h2>{{ $policy->profile?->full_name ?: auth()->user()->name }}</h2>
                    <p>{{ __('Policy number') }}: {{ $policy->policy_number }}</p>
                    <p>{{ __('Valid :start to :end', ['start' => $policy->start_date?->format('d M Y'), 'end' => $policy->end_date?->format('d M Y')]) }}</p>
                </div>
                <div class="hero-orbit" aria-hidden="true">
                    <div class="orbit-ring"></div>
                    <div class="orbit-core">{{ number_format((float) $policy->coverage_amount, 0) }}<small>TZS</small><span>{{ __('Annual cover') }}</span></div>
                </div>
            </section>

            <section class="metric-grid mt-6">
                <article class="metric-card"><span class="metric-label">{{ __('Member name') }}</span><strong>{{ $policy->profile?->full_name ?: auth()->user()->name }}</strong></article>
                <article class="metric-card"><span class="metric-label">{{ __('Email') }}</span><strong>{{ auth()->user()->email }}</strong></article>
                <article class="metric-card"><span class="metric-label">{{ __('Phone') }}</span><strong>{{ $policy->profile?->phone ?: '—' }}</strong></article>
                <article class="metric-card metric-card--accent"><span class="metric-label">{{ __('Annual premium paid') }}</span><strong>{{ number_format((float) $policy->premium, 0) }} <small>TZS</small></strong></article>
                <article class="metric-card"><span class="metric-label">{{ __('Coverage amount') }}</span><strong>{{ number_format((float) $policy->coverage_amount, 0) }} <small>TZS</small></strong></article>
            </section>

            <section class="surface-card mt-6">
                <div class="section-heading">
                    <div><span class="eyebrow">{{ __('Hospital network') }}</span><h3>{{ __('Approved hospitals') }} ({{ $hospitals->count() }})</h3></div>
                    <span class="status-pill status-pill--green">{{ __('Coverage active') }}</span>
                </div>
                @forelse($hospitals as $hospital)
                    <div class="admin-row">
                        <div><strong>{{ $hospital->organization_name }}</strong><small>{{ __('Hospital') }}{{ $hospital->license_number ? ' · '.__('License').': '.$hospital->license_number : '' }}</small>
                            <small>{{ __('Available services') }}: {{ $hospital->services->isNotEmpty() ? $hospital->services->pluck('name')->join(', ') : __('Contact hospital for available services') }}</small>
                        </div>
                        <span class="status-pill status-pill--green">{{ __('Approved') }}</span>
                    </div>
                @empty
                    <p class="empty-copy">{{ __('No approved hospitals are listed yet. Please contact support to confirm where this cover is accepted.') }}</p>
                @endforelse
                <p class="empty-copy mt-4">{{ __('The hospital count reflects approved hospitals currently listed in CredHealth. Confirm covered services and limits with the hospital before treatment.') }}</p>
            </section>
            <div class="mt-6"><button type="button" class="button button--green" onclick="window.print()">{{ __('Print insurance card') }}</button></div>
        @else
            <section class="surface-card">
                <span class="eyebrow">{{ __('No active policy') }}</span>
                <h3>{{ __('Your card will be available after premium payment is confirmed.') }}</h3>
                <p class="empty-copy">{{ __('Start the annual plan, approve the ClickPesa request on your phone, then return here after confirmation.') }}</p>
                <a href="{{ route('insurance.quote') }}" class="button button--green mt-4">{{ __('Get coverage') }}</a>
            </section>
        @endif
    </div>
</x-app-layout>
