<x-app-layout>
    <x-slot name="header"><div><p class="eyebrow">{{ $business->name }}</p><h1 class="page-title">Add transaction evidence</h1></div></x-slot>
    <div class="form-shell"><div class="form-intro"><span class="eyebrow">Verified activity</span><h2>Connect the activity behind your business.</h2><p>Add a phone transaction and CredAI will include it in your readiness calculation after verification.</p></div><form method="POST" action="{{ route('transactions.store') }}" class="workspace-form">@csrf
        <div class="form-field"><label for="phone_number">Phone number</label><input id="phone_number" name="phone_number" type="text" value="{{ old('phone_number', auth()->user()->phone) }}" required><x-input-error :messages="$errors->get('phone_number')" /></div>
        <div class="form-field"><label for="provider">Transaction provider</label><input id="provider" name="provider" type="text" placeholder="Mobile money or bank" value="{{ old('provider') }}" required><x-input-error :messages="$errors->get('provider')" /></div>
        <div class="form-row"><div class="form-field"><label for="reference">Reference</label><input id="reference" name="reference" type="text" value="{{ old('reference') }}" required></div><div class="form-field"><label for="amount">Amount</label><input id="amount" name="amount" type="number" min="1" step="0.01" value="{{ old('amount') }}" required></div></div>
        <div class="form-field"><label for="occurred_at">Transaction date</label><input id="occurred_at" name="occurred_at" type="datetime-local" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" required></div>
        <div class="form-actions"><a href="{{ route('dashboard') }}" class="form-cancel">Cancel</a><button type="submit" class="button button--green">Add evidence <span>&rarr;</span></button></div>
    </form></div>
</x-app-layout>
