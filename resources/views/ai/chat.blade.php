<x-app-layout>
    <x-slot name="header"><div><p class="eyebrow">{{ __('CredAI assistant') }}</p><h1 class="page-title">{{ __('Your protection guide') }}</h1><p class="page-subtitle">{{ __('Get context-aware help with quotes, payments, loans, claims, and next steps.') }}</p></div></x-slot>
    <main class="ai-assistant-page">
        @include('shared.ai-assistant', ['expanded' => true])
    </main>
</x-app-layout>
