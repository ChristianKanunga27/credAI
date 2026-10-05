<x-app-layout>
    <x-slot name="header"><div><p class="eyebrow">CredHealth</p><h1 class="page-title">{{ __('Provider workspace') }}</h1><p class="page-subtitle">{{ __('Manage your insurer profile and review assigned policies and claims.') }}</p></div></x-slot>
    <div class="dashboard-shell">
        @if(session('status'))<div class="workspace-status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="workspace-error">{{ $errors->first() }}</div>@endif
        <section class="surface-card provider-profile-card">
            <div class="section-heading"><div><span class="eyebrow">{{ __('Organization') }}</span><h3>{{ $provider ? __('Provider profile') : (auth()->user()->role === 'hospital' ? __('Register your hospital') : __('Register your insurer')) }}</h3></div>
                @if($provider)<span class="status-pill {{ $provider->status === 'approved' ? 'status-pill--green' : 'status-pill--amber' }}">{{ __(ucfirst($provider->status)) }}</span>@endif
            </div>
            @if(!$provider || $provider->status !== 'approved')
                <form method="POST" action="{{ route('provider.profile.store') }}" class="workspace-form">
                    @csrf @method('PUT')
                    <div class="form-field"><label for="organization_name">{{ __('Organization name') }}</label><input id="organization_name" name="organization_name" value="{{ old('organization_name', $provider?->organization_name) }}" required maxlength="255"></div>
                    <div class="form-field"><label for="license_number">{{ auth()->user()->role === 'hospital' ? __('Hospital license number') : __('Insurance license number') }}</label><input id="license_number" name="license_number" value="{{ old('license_number', $provider?->license_number) }}" required maxlength="255"></div>
                    <div class="form-actions"><button type="submit" class="button button--green">{{ __('Submit for review') }}</button></div>
                </form>
            @else
                <p class="empty-copy">{{ __('Your provider profile is approved. Contact an administrator to update licensed organization details.') }}</p>
            @endif
        </section>
        @if($provider?->status === 'approved')
            <section class="surface-card mt-6">
                <div class="section-heading"><div><span class="eyebrow">{{ __('Service catalog') }}</span><h3>{{ __('Publish a provider service') }}</h3></div></div>
                <p class="empty-copy">{{ __('Create services, update prices, or pause bookings from the service management page.') }}</p>
                <a class="button button--green" href="{{ route('provider.services.index') }}">{{ __('Manage services') }}</a>
            </section>
            <section class="surface-card mt-6">
                <div class="section-heading"><div><span class="eyebrow">{{ __('Mobile money') }}</span><h3>{{ __('Service payment requests') }}</h3></div></div>
                @forelse($servicePayments as $payment)
                    <div class="admin-row">
                        <div>
                            <strong>{{ $payment->providerService?->name }} · {{ $payment->user?->name }}</strong>
                            <small>{{ $payment->reference }} · TZS {{ number_format((float) $payment->amount, 2) }} · {{ $payment->phone }}</small>
                            @if($payment->status === 'paid')
                                <small>{{ __('Receipt') }} {{ $payment->provider_reference }} · {{ $payment->paid_at?->format('Y-m-d H:i') }}</small>
                            @endif
                        </div>
                        <span class="status-pill {{ $payment->status === 'paid' ? 'status-pill--green' : ($payment->status === 'pending' || $payment->status === 'processing' ? 'status-pill--amber' : 'status-pill--soft') }}">{{ __(ucfirst($payment->status)) }}</span>
                    </div>
                @empty
                    <p class="empty-copy">{{ __('No service payment requests yet.') }}</p>
                @endforelse
            </section>
        @elseif($provider)
            <p class="workspace-status mt-6">{{ __('Your organization must be approved before publishing services or receiving service-payment requests.') }}</p>
        @endif
        <div class="content-grid provider-data-grid">
            <section class="surface-card">
                <div class="section-heading"><div><span class="eyebrow">{{ __('Coverage') }}</span><h3>{{ __('Assigned policies') }}</h3></div></div>
                @forelse($policies as $policy)
                    <div class="admin-row"><div><strong>{{ $policy->policy_number }}</strong><small>{{ $policy->user?->name }} · TZS {{ number_format((float) $policy->premium, 0) }} {{ __('premium') }}</small></div><span class="status-pill status-pill--soft">{{ __(ucfirst($policy->status)) }}</span></div>
                @empty
                    <p class="empty-copy">{{ __('No assigned policies yet.') }}</p>
                @endforelse
            </section>
            <section class="surface-card">
                <div class="section-heading"><div><span class="eyebrow">{{ __('Service review') }}</span><h3>{{ __('Claims') }}</h3></div></div>
                @forelse($claims as $claim)
                    <div class="admin-row"><div><strong>{{ $claim->claim_number }} · {{ $claim->user?->name }}</strong><small>{{ $claim->policy?->policy_number }} · TZS {{ number_format((float) $claim->claim_amount, 0) }}</small></div><span class="status-pill status-pill--soft">{{ __(ucfirst(str_replace('_', ' ', $claim->status))) }}</span></div>
                @empty
                    <p class="empty-copy">{{ __('No assigned claims yet.') }}</p>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
