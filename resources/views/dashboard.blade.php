<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">{{ $roleLabel }} Dashboard</p>
                <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 mt-1">{{ greeting_label() }}, {{ Str::before(auth()->user()->name, ' ') }}</h1>
                <p class="text-gray-600 mt-2 text-sm">
                    @if($role === 'individual')
                        Overview of your protection, balance and quick actions.
                    @elseif($role === 'insurer')
                        Manage policies, claims and track your performance.
                    @else
                        Admin overview across the CredHealth platform.
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('insurance.quote') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 transition">Get Quote</a>
                @if($role === 'individual' && $activePolicy)
                    <a href="{{ route('insurance.card') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition">View Card</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        @if(session('status'))
            <div class="rounded-md bg-green-50 p-4 border border-green-200">
                <p class="text-sm text-green-800">{{ session('status') }}</p>
            </div>
        @endif

        @if($role === 'individual')
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Available Balance</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1">TZS {{ number_format($simBalance, 0) }}</p>
                    <p class="text-xs text-gray-500 mt-2">{{ $mobileMoneyTransactionCount }} transactions</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Annual Premium</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1">TZS {{ number_format((float) config('insurance.annual_premium', 50400), 0) }}</p>
                    <p class="text-xs text-gray-500 mt-2">Per year</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Coverage Amount</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1">TZS {{ number_format((float) config('insurance.annual_coverage', 100000), 0) }}</p>
                    <p class="text-xs text-gray-500 mt-2">Annual cover</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Risk Level</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1">{{ ucfirst($quote['risk_level']) }}</p>
                    <p class="text-xs text-gray-500 mt-2">AI assessment</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Recent Transactions</h2>
                            <p class="text-sm text-gray-500">Last 5 verified mobile money transactions</p>
                        </div>
                        <a href="{{ route('transactions.mobile-money.index') }}" class="text-sm text-green-600 hover:text-green-700">View all</a>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @forelse($mobileMoneyTransactions as $transaction)
                            <div class="px-5 py-3 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $transaction->provider ?: 'Mobile Money' }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $transaction->occurred_at->format('Y-m-d H:i') }} • {{ $transaction->reference }}</p>
                                </div>
                                <p class="text-sm font-semibold {{ $transaction->transaction_type === 'credit' ? 'text-green-600' : 'text-gray-900' }}">
                                    {{ $transaction->transaction_type === 'debit' ? '-' : '+' }}TZS {{ number_format((float) $transaction->amount, 0) }}
                                </p>
                            </div>
                        @empty
                            <div class="px-5 py-6 text-center text-sm text-gray-500">No transactions yet</div>
                        @endforelse
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
                        <p class="text-sm text-gray-500">Common tasks</p>
                    </div>
                    <div class="px-5 py-4 space-y-2">
                        <a href="{{ route('insurance.quote') }}" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">Get Insurance Quote</a>
                        <a href="{{ route('insurance.services.index') }}" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">Pay Provider Service</a>
                        <a href="{{ route('transactions.sync') }}" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md" onclick="event.preventDefault(); document.getElementById('sync-form').submit();">Sync Transactions</a>
                        <form id="sync-form" method="POST" action="{{ route('transactions.sync') }}" class="hidden">@csrf</form>
                    </div>
                </div>
            </div>
        @elseif($role === 'insurer')
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Total Policies</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $policyCount ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Claims</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $claimCount ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Active Policies</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $activePolicies ?? 0 }}</p>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Total Users</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $userCount ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Providers</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $providerCount ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Policies</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $policyCount ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Claims</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $claimCount ?? 0 }}</p>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
