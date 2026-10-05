<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="eyebrow">{{ __('CredHealth services') }}</p>
            <h1 class="page-title">{{ __('Manage services') }}</h1>
            <p class="page-subtitle">{{ __('Create and update the services customers can book from your approved organization.') }}</p>
        </div>
    </x-slot>

    <div class="dashboard-shell">
        @if(session('status'))<div class="workspace-status">{{ session('status') }}</div>@endif
        @if(session('error'))<div class="workspace-error">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="workspace-error">{{ $errors->first() }}</div>@endif

        @if(!$provider || $provider->status !== 'approved')
            <section class="surface-card">
                <h2>{{ __('Provider approval required') }}</h2>
                <p class="empty-copy">{{ __('Submit your organization details from the provider workspace. An administrator must approve your organization before you can publish services.') }}</p>
                <a class="button button--green" href="{{ route('provider.dashboard') }}">{{ __('Open provider workspace') }}</a>
            </section>
        @else
            <section class="surface-card">
                <div class="section-heading">
                    <div><span class="eyebrow">{{ __('Service catalog') }}</span><h2>{{ __('Create a service') }}</h2></div>
                </div>
                <form method="POST" action="{{ route('provider.services.store') }}" class="workspace-form">
                    @csrf
                    <div class="form-field"><label for="service-name">{{ __('Service name') }}</label><input id="service-name" name="name" value="{{ old('name') }}" maxlength="150" required></div>
                    <div class="form-field"><label for="service-description">{{ __('Description') }}</label><textarea id="service-description" name="description" maxlength="1000">{{ old('description') }}</textarea></div>
                    <div class="form-field"><label for="service-price">{{ __('Price (TZS)') }}</label><input id="service-price" name="price" type="number" min="1" max="100000000" step="0.01" value="{{ old('price') }}" required></div>
                    <button type="submit" class="button button--green">{{ __('Publish service') }}</button>
                </form>
            </section>

            <section class="surface-card mt-6">
                <div class="section-heading"><div><span class="eyebrow">{{ __('Customer catalog') }}</span><h2>{{ __('Your published services') }}</h2></div></div>
                <div class="admin-record-list">
                    @forelse($services as $service)
                        <form method="POST" action="{{ route('provider.services.update', $service) }}" class="admin-record">
                            @csrf @method('PATCH')
                            <div class="form-field"><label for="service-name-{{ $service->id }}">{{ __('Service name') }}</label><input id="service-name-{{ $service->id }}" name="name" value="{{ $service->name }}" maxlength="150" required></div>
                            <div class="form-field"><label for="service-description-{{ $service->id }}">{{ __('Description') }}</label><textarea id="service-description-{{ $service->id }}" name="description" maxlength="1000">{{ $service->description }}</textarea></div>
                            <div class="form-field"><label for="service-price-{{ $service->id }}">{{ __('Price (TZS)') }}</label><input id="service-price-{{ $service->id }}" name="price" type="number" min="1" max="100000000" step="0.01" value="{{ $service->price }}" required></div>
                            <input type="hidden" name="is_active" value="0">
                            <label><input type="checkbox" name="is_active" value="1" @checked($service->is_active)> {{ __('Available to customers') }}</label>
                            <button type="submit" class="admin-update-button">{{ __('Save service') }}</button>
                        </form>
                    @empty
                        <p class="empty-copy">{{ __('You have not published any services yet.') }}</p>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
