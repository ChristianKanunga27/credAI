<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>CredAI | Secure workspace</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="credai-auth-body">
        <main class="auth-shell">
            <section class="auth-story">
                <a href="{{ url('/') }}" class="auth-logo"><img src="{{ route('brand.logo') }}" alt="CredHealth - A CredAI Technologies Company"></a>
                <div class="auth-story-copy"><span class="auth-kicker">Responsible capital for real businesses</span><h1>Funding with proof behind it.</h1><p>Connect verified business activity with the right people, the right approvals, and the right capital.</p><div class="auth-proof"><span><b>30%</b> activity threshold</span><span><b>3</b> trusted roles</span></div></div>
                <a class="auth-back" href="{{ url('/') }}">&larr; Back to CredAI home</a>
            </section>
            <section class="auth-form-panel"><div class="auth-form-header"><span class="auth-kicker">CredAI workspace</span><button type="button" class="auth-language" onclick="window.location.href='{{ route('language.switch', app()->getLocale() === 'sw' ? 'en' : 'sw') }}'">{{ app()->getLocale() === 'sw' ? 'English' : 'Kiswahili' }}</button></div><div class="auth-form-card">{{ $slot }}</div><p class="auth-help">Need help? <a href="mailto:support@credai.co.tz">support@credai.co.tz</a></p></section>
        </main>
    </body>
</html>
