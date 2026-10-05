<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

        <title>CredHealth | Workspace</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased" x-data="{ dark: localStorage.getItem('credai-theme') === 'dark', sidebarOpen: false }" :class="{ 'dark': dark }">
        <div class="workspace-layout">
            @include('layouts.navigation')
            <div class="workspace-main">
                <header class="workspace-topbar">
                    <button type="button" class="sidebar-menu-toggle" @click="sidebarOpen = true" aria-label="{{ __('Open navigation') }}" aria-controls="workspace-sidebar">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </button>
                    <div class="workspace-topbar-context">{{ __('CredHealth workspace') }}</div>
                    <div class="workspace-topbar-actions">
                        <select class="app-language-select" aria-label="{{ __('Language') }}" onchange="window.location.href = this.value">
                            <option value="{{ route('language.switch', 'en') }}" @selected(app()->getLocale() === 'en')>EN</option>
                            <option value="{{ route('language.switch', 'sw') }}" @selected(app()->getLocale() === 'sw')>SW</option>
                        </select>
                        <button type="button" class="theme-toggle" aria-label="{{ __('Toggle color theme') }}" @click="dark = ! dark; localStorage.setItem('credai-theme', dark ? 'dark' : 'light')">
                            <span x-text="dark ? 'Light' : 'Dark'"></span>
                        </button>
                    </div>
                </header>

                @isset($header)
                    @php
                        $workspaceArtwork = match (true) {
                            request()->routeIs('admin.*') => ['path' => 'images/two.jpg', 'alt' => __('Hospital exterior')],
                            request()->routeIs('provider.*') => ['path' => 'images/onejpg', 'alt' => __('Doctor discussing care with a patient')],
                            request()->routeIs('insurance.*') => ['path' => 'images/hospital.jpeg', 'alt' => __('Caregiver supporting an older adult')],
                            request()->routeIs('ai.*') => ['path' => 'images/onejpg', 'alt' => __('Doctor discussing care with a patient')],
                            request()->routeIs('business.*', 'funding.*', 'transactions.*') => ['path' => 'images/three.jpg', 'alt' => __('Customer using a mobile phone')],
                            request()->routeIs('profile.*') => ['path' => 'images/hospital.jpeg', 'alt' => __('Caregiver supporting an older adult')],
                            request()->routeIs('dashboard') && auth()->user()?->role === 'business' => ['path' => 'images/three.jpg', 'alt' => __('Customer using a mobile phone')],
                            request()->routeIs('dashboard') && in_array(auth()->user()?->role, ['insurer', 'provider', 'hospital'], true) => ['path' => 'images/onejpg', 'alt' => __('Doctor discussing care with a patient')],
                            request()->routeIs('dashboard') => ['path' => 'images/hospital.jpeg', 'alt' => __('Caregiver supporting an older adult')],
                            default => ['path' => 'images/three.jpg', 'alt' => __('Customer using a mobile phone')],
                        };
                    @endphp
                    <header class="app-page-header">
                        <div class="app-page-header-inner max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            <div class="app-page-header-copy">{{ $header }}</div>
                            <img class="app-page-header-image" src="{{ asset($workspaceArtwork['path']) }}" alt="" aria-hidden="true">
                        </div>
                    </header>
                @endisset

                <main>{{ $slot }}</main>
            </div>
        </div>
        @unless(request()->routeIs('ai.index'))
            @include('shared.ai-assistant')
        @endunless
    </body>
</html>
