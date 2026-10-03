<nav x-data="{ open: false }" class="app-navigation">
    @php $userRole = auth()->user()?->role; @endphp
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="app-brand">
                        <img class="app-brand-logo" src="{{ route('brand.logo') }}" alt="CredHealth - A CredAI Technologies Company">
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex app-nav-links">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('ai.index')" :active="request()->routeIs('ai.*')">{{ __('AI guide') }}</x-nav-link>
                    @if (in_array($userRole, ['admin'], true))
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">{{ __('Admin control') }}</x-nav-link>
                    @endif
                    @if (in_array($userRole, ['individual', 'business'], true))
                        <x-nav-link :href="route('insurance.quote')" :active="request()->routeIs('insurance.*')">Smart quote</x-nav-link>
                    @endif
                    @if (in_array($userRole, ['insurer', 'provider', 'hospital'], true))
                        <x-nav-link :href="route('provider.dashboard')" :active="request()->routeIs('provider.*')">{{ __('Provider workspace') }}</x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <button type="button" class="theme-toggle" aria-label="Toggle color theme" @click="dark = ! dark; localStorage.setItem('credai-theme', dark ? 'dark' : 'light')">
                    <span x-text="dark ? 'Light' : 'Dark'"></span>
                </button>
                <select class="app-language-select" aria-label="Language" onchange="window.location.href = this.value"><option value="{{ route('language.switch', 'en') }}" @selected(app()->getLocale() === 'en')>EN</option><option value="{{ route('language.switch', 'sw') }}" @selected(app()->getLocale() === 'sw')>SW</option></select>
                @auth
                    <a class="app-account-link" href="{{ route('profile.edit') }}">{{ __('Settings') }}</a>
                    <form method="POST" action="{{ route('logout') }}" class="app-logout-form">
                        @csrf
                        <button type="submit" class="app-logout-button">{{ __('Log Out') }}</button>
                    </form>
                @endauth
                {{--
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="app-user-trigger">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown> --}}
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1 app-mobile-links">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('ai.index')" :active="request()->routeIs('ai.*')">{{ __('AI guide') }}</x-responsive-nav-link>
            @if (in_array($userRole, ['individual', 'business'], true))
                <x-responsive-nav-link :href="route('insurance.quote')">Smart quote</x-responsive-nav-link>
            @endif
            @if (in_array($userRole, ['insurer', 'provider', 'hospital'], true))
                <x-responsive-nav-link :href="route('provider.dashboard')">{{ __('Provider workspace') }}</x-responsive-nav-link>
            @endif
            @if ($userRole === 'admin')
                <x-responsive-nav-link :href="route('admin.dashboard')">{{ __('Admin control') }}</x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        @auth
            <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('Settings & password') }}
                    </x-responsive-nav-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit" class="app-mobile-logout">{{ __('Log Out') }}</button>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</nav>
