<x-app-layout>
    <x-slot name="header"><div><p class="eyebrow">{{ __('CredAI assistant') }}</p><h1 class="page-title">{{ __('AI protection guide') }}</h1><p class="page-subtitle">{{ __('Clear, practical guidance for your insurance journey.') }}</p></div></x-slot>
    <div class="ai-assistant-page">
        <section class="ai-chat-intro" aria-labelledby="ai-chat-heading">
            <div class="ai-chat-intro-copy">
                <span class="ai-chat-status"><span aria-hidden="true"></span>{{ __('CredAI assistant is ready') }}</span>
                <h2 id="ai-chat-heading">{{ __('How can we help you today?') }}</h2>
                <p>{{ __('Ask a question or choose a topic to get started. Your assistant can guide you through CredAI, but cannot approve applications or move money.') }}</p>
            </div>
            <div class="ai-chat-topics" aria-label="{{ __('Suggested questions') }}">
                <button type="button" data-ai-prompt="{{ __('How do I get an insurance quote?') }}">{{ __('Getting a quote') }}</button>
                <button type="button" data-ai-prompt="{{ __('What cover options are available?') }}">{{ __('Cover options') }}</button>
                <button type="button" data-ai-prompt="{{ __('How do payments work?') }}">{{ __('Payments') }}</button>
                <button type="button" data-ai-prompt="{{ __('How can I check a claim?') }}">{{ __('Claims') }}</button>
            </div>
        </section>

        <section class="ai-chat-workspace" aria-label="{{ __('Chat with the CredAI assistant') }}">
            @include('shared.ai-assistant', ['expanded' => true])
            <p class="ai-chat-safety">{{ __('For your safety, never share passwords, PINs, OTPs, or private account and medical details.') }}</p>
        </section>
    </div>

    <script>
        document.querySelectorAll('[data-ai-prompt]').forEach((prompt) => {
            prompt.addEventListener('click', () => {
                const input = document.querySelector('.ai-assistant-page [data-ai-input]');

                if (input) {
                    input.value = prompt.dataset.aiPrompt;
                    input.focus();
                }
            });
        });
    </script>
</x-app-layout>
