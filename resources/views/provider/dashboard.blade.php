<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Provider Portal</p>
                <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 dark:text-white mt-1">Provider Dashboard</h1>
                <p class="text-gray-600 dark:text-gray-300 mt-2 text-sm">Manage your organization, services and client cases</p>
            </div>
            @if($provider?->status === 'approved')
                <a href="{{ route('provider.services.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white dark:text-white bg-green-600 dark:bg-green-500 rounded-md hover:bg-green-700 dark:hover:bg-green-600 transition">Manage Services</a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        @if(session('status'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-700">
                <p class="text-sm text-green-800 dark:text-green-200">{{ session('status') }}</p>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Organization Profile</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $provider ? 'Registration details' : 'Complete provider registration' }}</p>
                </div>
                @if($provider)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $provider->status === 'approved' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300' }}">{{ ucfirst($provider->status) }}</span>
                @endif
            </div>
            <div class="px-5 py-4">
                @if(!$provider || $provider->status !== 'approved')
                    <form method="POST" action="{{ route('provider.profile.store') }}" class="space-y-4 max-w-xl">
                        @csrf @method('PUT')
                        <div>
                            <label for="organization_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Organization Name</label>
                            <input type="text" id="organization_name" name="organization_name" value="{{ old('organization_name', $provider?->organization_name) }}" required maxlength="255" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                        </div>
                        <div>
                            <label for="license_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">License Number</label>
                            <input type="text" id="license_number" name="license_number" value="{{ old('license_number', $provider?->license_number) }}" required maxlength="255" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                        </div>
                        <div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white dark:text-white bg-green-600 dark:bg-green-500 rounded-md hover:bg-green-700 dark:hover:bg-green-600">Submit for Review</button>
                        </div>
                    </form>
                @else
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $provider->organization_name }} • License: {{ $provider->license_number }}</p>
                @endif
            </div>
        </div>

        @if($provider?->status === 'approved')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Assigned Policies</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Policies under your management</p>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($policies as $policy)
                            <div class="px-5 py-3">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $policy->policy_number }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $policy->user?->name }} • TZS {{ number_format((float) $policy->premium, 0) }}</p>
                            </div>
                        @empty
                            <div class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No policies assigned</div>
                        @endforelse
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Claims</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Claims requiring attention</p>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($claims as $claim)
                            <div class="px-5 py-3">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $claim->claim_number }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $claim->user?->name }} • TZS {{ number_format((float) $claim->claim_amount, 0) }} • {{ ucwords(str_replace('_', ' ', $claim->status)) }}</p>
                            </div>
                        @empty
                            <div class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No claims available</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
