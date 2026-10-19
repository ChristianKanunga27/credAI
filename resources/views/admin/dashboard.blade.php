<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Admin</p>
                <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 mt-1">{{ __('Administration Dashboard') }}</h1>
                <p class="text-gray-600 mt-2 text-sm">Platform overview and key metrics</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <a href="{{ route('admin.providers') }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
                <p class="text-sm text-gray-500">Insurance Providers</p>
                <p class="text-2xl font-semibold text-gray-900 mt-1">{{ number_format($providerCount) }}</p>
                <p class="text-xs text-amber-600 mt-2">{{ $pendingProviders }} pending</p>
            </a>
            <a href="{{ route('admin.policies') }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
                <p class="text-sm text-gray-500">Policies</p>
                <p class="text-2xl font-semibold text-gray-900 mt-1">{{ number_format($policyCount) }}</p>
                <p class="text-xs text-green-600 mt-2">{{ $activePolicies }} active</p>
            </a>
            <a href="{{ route('admin.claims') }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
                <p class="text-sm text-gray-500">Claims</p>
                <p class="text-2xl font-semibold text-gray-900 mt-1">{{ number_format($claimCount) }}</p>
                <p class="text-xs text-amber-600 mt-2">{{ $pendingClaims }} pending</p>
            </a>
            <a href="{{ route('admin.payments') }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
                <p class="text-sm text-gray-500">Payments</p>
                <p class="text-2xl font-semibold text-gray-900 mt-1">{{ number_format($paymentCount) }}</p>
                <p class="text-xs text-gray-500 mt-2">{{ $pendingPayments }} pending · {{ $successfulPayments }} paid</p>
            </a>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                <p class="text-sm text-gray-500">Registered Users</p>
                <p class="text-2xl font-semibold text-gray-900 mt-1">{{ number_format($userCount) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Quick Links</h2>
                <p class="text-sm text-gray-500">Admin workspaces</p>
            </div>
            <div class="px-5 py-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <a href="{{ route('admin.providers') }}" class="px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">Review Providers</a>
                <a href="{{ route('admin.policies') }}" class="px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">Manage Policies</a>
                <a href="{{ route('admin.claims') }}" class="px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">Review Claims</a>
                <a href="{{ route('admin.payments') }}" class="px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">Process Payments</a>
                <a href="{{ route('admin.loans') }}" class="px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">Review Loans</a>
                <a href="{{ route('admin.activity') }}" class="px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">Activity Log</a>
            </div>
        </div>
    </div>
</x-app-layout>
