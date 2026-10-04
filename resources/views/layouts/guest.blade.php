<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>CredAI | Secure workspace</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="credai-auth-body" x-data="{ dark: localStorage.getItem('credai-theme') === 'dark' }" :class="{ 'dark': dark }">
        <main class="auth-shell">
            <section class="auth-story">
                <a href="{{ url('/') }}" class="auth-logo"><img src="{{ route('brand.logo') }}" alt="CredHealth - A CredAI Technologies Company"></a>
                <div class="auth-story-copy"><span class="auth-kicker">{{ __('Protection built on real value') }}</span><h1>{{ __('Smarter insurance, faster decisions.') }}</h1><p>{{ __('Connect mobile balance, real protection goals, and AI support to deliver fairer, easier cover.') }}</p><div class="auth-proof"><span><b>SIM</b> {{ __('balance check') }}</span><span><b>3</b> {{ __('trusted roles') }}</span></div></div>
                <a class="auth-back" href="{{ url('/') }}">&larr; {{ __('Back to CredAI home') }}</a>
            </section>
            <section class="auth-form-panel">
                <div class="auth-form-header">
                    <span class="auth-kicker">{{ __('CredAI workspace') }}</span>
                    <div class="flex items-center gap-2">
                        <button type="button" class="theme-toggle" aria-label="Toggle color theme" @click="dark = ! dark; localStorage.setItem('credai-theme', dark ? 'dark' : 'light')">
                            <span x-text="dark ? @js(__('Light')) : @js(__('Dark'))"></span>
                        </button>
                        <button type="button" class="auth-language" onclick="window.location.href='{{ route('language.switch', app()->getLocale() === 'sw' ? 'en' : 'sw') }}'">{{ app()->getLocale() === 'sw' ? 'English' : 'Kiswahili' }}</button>
                    </div>
                </div>
                <div class="auth-form-card">{{ $slot }}</div>
                <p class="auth-help">{{ __('Need help?') }} <a href="mailto:support@credai.co.tz">support@credai.co.tz</a></p>
            </section>
        </main>
        @include('shared.ai-assistant')
    </body>
</html>
