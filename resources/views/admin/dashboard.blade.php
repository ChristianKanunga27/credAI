<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Administration</p>
                <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 dark:text-white mt-1">{{ __('Admin Dashboard') }}</h1>
                <p class="text-gray-600 dark:text-gray-300 mt-2 text-sm">Real-time platform metrics and management tools</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <a href="{{ route('admin.providers') }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md dark:hover:shadow-gray-900/20 transition">
                <p class="text-sm text-gray-500 dark:text-gray-400">Insurance Providers</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">{{ number_format($providerCount) }}</p>
                <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">{{ $pendingProviders }} awaiting review</p>
            </a>
            <a href="{{ route('admin.policies') }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md dark:hover:shadow-gray-900/20 transition">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Policies</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">{{ number_format($policyCount) }}</p>
                <p class="text-xs text-green-600 dark:text-green-400 mt-2">{{ $activePolicies }} active</p>
            </a>
            <a href="{{ route('admin.claims') }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md dark:hover:shadow-gray-900/20 transition">
                <p class="text-sm text-gray-500 dark:text-gray-400">Claims Submitted</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">{{ number_format($claimCount) }}</p>
                <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">{{ $pendingClaims }} pending review</p>
            </a>
            <a href="{{ route('admin.payments') }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md dark:hover:shadow-gray-900/20 transition">
                <p class="text-sm text-gray-500 dark:text-gray-400">Payment Requests</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">{{ number_format($paymentCount) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ $pendingPayments }} pending • {{ $successfulPayments }} completed</p>
            </a>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Registered Users</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">{{ number_format($userCount) }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Access</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Navigate to admin workspaces</p>
            </div>
            <div class="px-5 py-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                <a href="{{ route('admin.providers') }}" class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition">Manage Providers</a>
                <a href="{{ route('admin.policies') }}" class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition">Manage Policies</a>
                <a href="{{ route('admin.claims') }}" class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition">Process Claims</a>
                <a href="{{ route('admin.payments') }}" class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition">Process Payments</a>
                <a href="{{ route('admin.loans') }}" class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition">Review Loan Applications</a>
                <a href="{{ route('admin.activity') }}" class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition">System Activity Log</a>
            </div>
        </div>
    </div>
</x-app-layout>
