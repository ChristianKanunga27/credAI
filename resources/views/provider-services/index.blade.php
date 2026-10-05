<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="eyebrow">{{ __('CredHealth services') }}</p>
            <h1 class="page-title">{{ __('Hospital and provider services') }}</h1>
            <p class="page-subtitle">{{ __('Select an approved provider service, then use a USSD prompt or mobile-money control number.') }}</p>
        </div>
    </x-slot>

    <div class="dashboard-shell">
        @if(session('status'))<div class="workspace-status">{{ session('status') }}</div>@endif
        @if(session('error'))<div class="workspace-error">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="workspace-error">{{ $errors->first() }}</div>@endif

        <section class="surface-card mb-6">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">{{ __('Secure checkout') }}</span>
                    <p class="empty-copy">{{ __('Choose an instant USSD prompt or a control number to pay through your mobile-money menu.') }}</p>
                </div>
            </div>
        </section>

        <section class="surface-card">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">{{ __('Approved catalog') }}</span>
                    <h3>{{ __('Choose a service') }}</h3>
                </div>
            </div>

            <div class="content-grid">
                @forelse($services as $service)
                    <article class="surface-card">
                        <span class="eyebrow">{{ __(ucfirst($service->provider->provider_type)) }}</span>
                        <h3>{{ $service->name }}</h3>
                        <p>{{ $service->description }}</p>
                        <p><strong>{{ $service->provider->organization_name }}</strong></p>
                        <p><strong>TZS {{ number_format((float) $service->price, 2) }}</strong></p>
                        <p class="empty-copy">{{ __('Approve the ClickPesa payment prompt on your phone to complete payment.') }}</p>
                        <form method="POST" action="{{ route('insurance.services.pay', $service) }}" class="workspace-form">
                            @csrf
                            <div class="form-field">
                                <label for="service-method-{{ $service->id }}">{{ __('Mobile-money payment method') }}</label>
                                <select id="service-method-{{ $service->id }}" name="collection_method" required>
                                    <option value="ussd">{{ __('USSD phone prompt') }}</option>
                                    <option value="control_number">{{ __('Control number') }}</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="service-phone-{{ $service->id }}">{{ __('Mobile-money phone number') }}</label>
                                <input id="service-phone-{{ $service->id }}" name="phone" type="tel" maxlength="30" value="{{ old('phone', $phone) }}" required>
                            </div>
                            <button type="submit" class="button button--green">{{ __('Send payment prompt to my phone') }}</button>
                        </form>
                    </article>
                @empty
                    <p class="empty-copy">{{ __('There are no approved provider services available yet.') }}</p>
                @endforelse
            </div>

            @if($services->hasPages())
                <div class="admin-pagination">{{ $services->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
