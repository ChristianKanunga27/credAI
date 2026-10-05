<x-app-layout>
    <x-slot name="header"><div><p class="eyebrow">CredHealth funding desk</p><h1 class="page-title">Start an application</h1></div></x-slot>
    <div class="form-shell"><div class="form-intro"><span class="eyebrow">Step 1 of 3</span><h2>Tell providers what you want to build.</h2><p>Your verified activity and hospital partner will give this request the context it needs.</p></div><form method="POST" action="{{ route('funding.store') }}" class="workspace-form">@csrf
        <div class="form-field"><label for="requested_amount">Requested amount</label><input id="requested_amount" name="requested_amount" type="number" min="1" step="0.01" value="{{ old('requested_amount') }}" required><x-input-error :messages="$errors->get('requested_amount')" /></div>
        <div class="form-field"><label for="purpose">What will the funding support?</label><textarea id="purpose" name="purpose" rows="4" required>{{ old('purpose') }}</textarea><x-input-error :messages="$errors->get('purpose')" /></div>
        <div class="form-field"><label for="provider_id">Preferred fund provider <span>(optional)</span></label><select id="provider_id" name="provider_id"><option value="">Let CredAI match me</option>@foreach($providers as $provider)<option value="{{ $provider->id }}">{{ $provider->organization_name }} · {{ number_format($provider->available_funds, 0) }} available</option>@endforeach</select></div>
        <div class="form-field"><label for="hospital_id">Hospital partner <span>(optional)</span></label><select id="hospital_id" name="hospital_id"><option value="">Choose later</option>@foreach($hospitals as $hospital)<option value="{{ $hospital->id }}">{{ $hospital->name }}</option>@endforeach</select></div>
        <div class="form-actions"><a href="{{ route('dashboard') }}" class="form-cancel">Cancel</a><button type="submit" class="button button--green">Submit application <span>&rarr;</span></button></div>
    </form></div>
</x-app-layout>
