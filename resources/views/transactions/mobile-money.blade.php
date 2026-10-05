<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="eyebrow">{{ __('Mobile money') }}</p>
            <h1 class="page-title">{{ __('Verified transaction history') }}</h1>
            <p class="page-subtitle">{{ __('Your verified credits and debits, with the amount available for premium and service requests.') }}</p>
        </div>
    </x-slot>

    <div class="dashboard-shell">
        <section class="surface-card">
            <div class="section-heading">
                <div><span class="eyebrow">{{ __('Available transaction balance') }}</span><h3>TZS {{ number_format($availableBalance, 2) }}</h3></div>
                <form method="POST" action="{{ route('transactions.sync') }}">
                    @csrf
                    <button type="submit" class="button button--green">{{ __('Sync transactions') }}</button>
                </form>
            </div>

            <div class="admin-record-list">
                @forelse($transactions as $transaction)
                    <article class="admin-row">
                        <div>
                            <strong>{{ $transaction->provider ?: __('Mobile money') }} · {{ $transaction->reference }}</strong>
                            <small>{{ $transaction->occurred_at->format('Y-m-d H:i') }} · {{ __(ucfirst($transaction->transaction_type)) }}</small>
                        </div>
                        <strong>{{ $transaction->transaction_type === 'debit' ? '−' : '+' }} TZS {{ number_format((float) $transaction->amount, 2) }}</strong>
                    </article>
                @empty
                    <p class="empty-copy">{{ __('No verified mobile-money transactions have been synced yet.') }}</p>
                @endforelse
            </div>

            @if($transactions->hasPages())
                <div class="admin-pagination">{{ $transactions->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
