<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $roleLabel }}</p>
                <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 dark:text-white mt-1">{{ greeting_label() }}, {{ Str::before(auth()->user()->name, ' ') }}</h1>
                <p class="text-gray-600 dark:text-gray-300 mt-2 text-sm">
                    @if($role === 'individual')
                        Your protection overview at a glance.
                    @elseif($role === 'insurer')
                        Underwriting and policy management.
                    @else
                        Platform-wide administration insights.
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('insurance.quote') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white dark:text-white bg-green-600 dark:bg-green-500 rounded-md hover:bg-green-700 dark:hover:bg-green-600 transition">Get Quote</a>
                @if($role === 'individual' && $activePolicy)
                    <a href="{{ route('insurance.card') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition">Insurance Card</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        @if(session('status'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-700">
                <p class="text-sm text-green-800 dark:text-green-200">{{ session('status') }}</p>
            </div>
        @endif

        @if($role === 'individual')
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Mobile Money Balance</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">TZS {{ number_format($simBalance, 0) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ $mobileMoneyTransactionCount }} verified transactions</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Annual Premium</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">TZS {{ number_format((float) config('insurance.annual_premium', 50400), 0) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Due annually</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Coverage</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">TZS {{ number_format((float) config('insurance.annual_coverage', 100000), 0) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Annual coverage limit</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Risk Assessment</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1 capitalize">{{ $quote['risk_level'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">AI-generated</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Activity</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Latest mobile money transactions</p>
                        </div>
                        <a href="{{ route('transactions.mobile-money.index') }}" class="text-sm text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300">View All</a>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($mobileMoneyTransactions as $transaction)
                            <div class="px-5 py-3 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $transaction->provider ?: 'Mobile Money' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $transaction->occurred_at->format('Y-m-d H:i') }} • {{ $transaction->reference }}</p>
                                </div>
                                <p class="text-sm font-semibold {{ $transaction->transaction_type === 'credit' ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ $transaction->transaction_type === 'debit' ? '-' : '+' }}TZS {{ number_format((float) $transaction->amount, 0) }}
                                </p>
                            </div>
                        @empty
                            <div class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No transactions found</div>
                        @endforelse
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Actions</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Frequently used tasks</p>
                    </div>
                    <div class="px-5 py-4 space-y-1">
                        <a href="{{ route('insurance.quote') }}" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md">Generate Insurance Quote</a>
                        <a href="{{ route('insurance.services.index') }}" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md">Pay for Services</a>
                        <a href="{{ route('transactions.sync') }}" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md" onclick="event.preventDefault(); document.getElementById('sync-form').submit();">Sync Mobile Money</a>
                        <form id="sync-form" method="POST" action="{{ route('transactions.sync') }}" class="hidden">@csrf</form>
                    </div>
                </div>
            </div>
        @elseif($role === 'insurer')
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Policies</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">{{ $policyCount ?? 0 }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Claims</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">{{ $claimCount ?? 0 }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Active Policies</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">{{ $activePolicies ?? 0 }}</p>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Registered Users</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">{{ number_format($userCount ?? 0) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Providers</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">{{ number_format($providerCount ?? 0) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Policies</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">{{ number_format($policyCount ?? 0) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Claims</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">{{ number_format($claimCount ?? 0) }}</p>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
