@php
    $user = auth()->user();
    $userRole = $user?->role;
@endphp

<div class="workspace-sidebar-wrap">
    <button type="button" class="sidebar-backdrop" x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" aria-label="{{ __('Close navigation') }}"></button>

    <aside id="workspace-sidebar" class="workspace-sidebar" :class="{ 'workspace-sidebar--open': sidebarOpen }" aria-label="{{ __('Main navigation') }}">
        <a href="{{ route('dashboard') }}" class="sidebar-brand">
            <img src="{{ route('brand.logo') }}" alt="CredHealth" onerror="this.hidden=true; this.nextElementSibling.hidden=false">
            <span class="sidebar-brand-fallback" hidden><span class="sidebar-brand-mark">C</span><span>CredHealth<small>BY CREDAI TECHNOLOGIES</small></span></span>
        </a>

        <div class="sidebar-section-label">{{ __('Workspace') }}</div>
        <nav class="sidebar-links">
            <a href="{{ route('dashboard') }}" @class(['sidebar-link', 'is-active' => request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="8" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="15" width="7" height="6" rx="1.5"/></svg>
                <span>{{ __('Dashboard') }}</span>
            </a>

            @if(in_array($userRole, ['individual', 'business'], true))
                <a href="{{ route('insurance.quote') }}" @class(['sidebar-link', 'is-active' => request()->routeIs('insurance.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 20 6v5c0 5.2-3.4 8.4-8 10-4.6-1.6-8-4.8-8-10V6l8-3Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>
                    <span>{{ __('Smart quote') }}</span>
                </a>
            @endif

            @if(in_array($userRole, ['insurer', 'provider', 'hospital'], true))
                <a href="{{ route('provider.dashboard') }}" @class(['sidebar-link', 'is-active' => request()->routeIs('provider.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 10h.01M15 10h.01M9 14h.01M15 14h.01M10 21v-4h4v4"/></svg>
                    <span>{{ __('Provider workspace') }}</span>
                </a>
                <a href="{{ route('provider.services.index') }}" @class(['sidebar-link', 'is-active' => request()->routeIs('provider.services.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/></svg>
                    <span>{{ __('Manage services') }}</span>
                </a>
            @endif

            @if($userRole === 'admin')
                <a href="{{ route('admin.dashboard') }}" @class(['sidebar-link', 'is-active' => request()->routeIs('admin.dashboard')]) @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5M4 19h17M7 15l4-4 3 2 6-7"/><path d="M17 6h3v3"/></svg>
                    <span>{{ __('Admin control') }}</span>
                </a>
                <div class="sidebar-admin-links" aria-label="{{ __('Administration sections') }}">
                    <a @class(['sidebar-link', 'sidebar-link--nested', 'is-active' => request()->routeIs('admin.dashboard')]) href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>{{ __('Overview') }}</a>
                    <a @class(['sidebar-link', 'sidebar-link--nested', 'is-active' => request()->routeIs('admin.providers')]) href="{{ route('admin.providers') }}" @if(request()->routeIs('admin.providers')) aria-current="page" @endif>{{ __('Providers') }}</a>
                    <a @class(['sidebar-link', 'sidebar-link--nested', 'is-active' => request()->routeIs('admin.policies')]) href="{{ route('admin.policies') }}" @if(request()->routeIs('admin.policies')) aria-current="page" @endif>{{ __('Policies') }}</a>
                    <a @class(['sidebar-link', 'sidebar-link--nested', 'is-active' => request()->routeIs('admin.claims')]) href="{{ route('admin.claims') }}" @if(request()->routeIs('admin.claims')) aria-current="page" @endif>{{ __('Claims') }}</a>
                    <a @class(['sidebar-link', 'sidebar-link--nested', 'is-active' => request()->routeIs('admin.payments')]) href="{{ route('admin.payments') }}" @if(request()->routeIs('admin.payments')) aria-current="page" @endif>{{ __('Payments') }}</a>
                    <a @class(['sidebar-link', 'sidebar-link--nested', 'is-active' => request()->routeIs('admin.loans')]) href="{{ route('admin.loans') }}" @if(request()->routeIs('admin.loans')) aria-current="page" @endif>{{ __('Loans') }}</a>
                    <a @class(['sidebar-link', 'sidebar-link--nested', 'is-active' => request()->routeIs('admin.activity')]) href="{{ route('admin.activity') }}" @if(request()->routeIs('admin.activity')) aria-current="page" @endif>{{ __('Activity log') }}</a>
                </div>
            @endif

            <a href="{{ route('ai.index') }}" @class(['sidebar-link', 'is-active' => request()->routeIs('ai.*')])>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v3m0 12v3M3 12h3m12 0h3M5.6 5.6l2.1 2.1m8.6 8.6 2.1 2.1m0-12.8-2.1 2.1m-8.6 8.6-2.1 2.1"/><circle cx="12" cy="12" r="5"/></svg>
                <span>{{ __('AI guide') }}</span><span class="sidebar-ai-tag">AI</span>
            </a>
        </nav>

        <div class="sidebar-spacer"></div>
        <div class="sidebar-section-label">{{ __('Account') }}</div>
        <a href="{{ route('profile.edit') }}" @class(['sidebar-link', 'is-active' => request()->routeIs('profile.*')])>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
            <span>{{ __('Profile and settings') }}</span>
        </a>
        <div class="sidebar-account">
            <span class="sidebar-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user?->name ?? 'C', 0, 1)) }}</span>
            <span class="sidebar-account-copy"><strong>{{ $user?->name }}</strong><small>{{ ucfirst($userRole ?? '') }}</small></span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-logout" aria-label="{{ __('Log out') }}" title="{{ __('Log out') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
                </button>
            </form>
        </div>
    </aside>
</div>
