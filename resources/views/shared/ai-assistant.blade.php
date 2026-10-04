@php
    $assistantContext = match (true) {
        request()->routeIs('admin.*') => 'admin',
        request()->routeIs('provider.*') => 'provider',
        request()->routeIs('insurance.*') => 'quote',
        request()->routeIs('dashboard') => 'dashboard',
        request()->routeIs('login', 'register', 'password.*') => 'auth',
        request()->routeIs('profile.*') => 'profile',
        request()->path() === '/' => 'public',
        default => 'general',
    };
    $assistantExpanded = $expanded ?? false;
@endphp

<div class="ai-assistant-widget {{ $assistantExpanded ? 'ai-assistant-widget--expanded' : '' }}" data-ai-assistant data-endpoint="{{ route('ai.chat') }}" data-page="{{ $assistantContext }}">
    @unless($assistantExpanded)
        <button type="button" class="ai-assistant-launch" data-ai-launch aria-expanded="false" aria-controls="credai-assistant-panel" aria-label="{{ __('Open CredAI assistant') }}" title="{{ __('Open CredAI assistant') }}">
            <span class="ai-assistant-launch-mark" aria-hidden="true">
                <img src="{{ route('brand.logo') }}" alt="">
            </span>
            <span class="ai-assistant-launch-copy"><strong>{{ __('Ask CredAI') }}</strong><small>{{ __('Protection guide') }}</small></span>
        </button>
    @endunless

    <section id="credai-assistant-panel" class="ai-assistant-panel" data-ai-panel @if(!$assistantExpanded) hidden @endif aria-label="{{ __('CredAI assistant') }}">
        <header class="ai-assistant-header">
            <span class="ai-assistant-avatar" aria-hidden="true">
                <img src="{{ route('brand.logo') }}" alt="">
            </span>
            <div class="ai-assistant-heading">
                <span class="ai-badge">{{ __('CredAI assistant') }}</span>
                <h2>{{ $assistantExpanded ? __('CredAI protection guide') : __('Protection guide') }}</h2>
                <span class="ai-assistant-presence"><span aria-hidden="true"></span>{{ __('Ready to help') }}</span>
            </div>
            @unless($assistantExpanded)
                <button type="button" class="ai-assistant-close" data-ai-close aria-label="{{ __('Close assistant') }}" title="{{ __('Close assistant') }}">
                    <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="m5 5 10 10M15 5 5 15"/></svg>
                </button>
            @endunless
        </header>
        <p class="ai-assistant-intro">{{ __('Ask about cover, quotes, payments, loans, claims, or using this page.') }}</p>
        <div class="ai-assistant-messages" data-ai-messages role="log" aria-live="polite" aria-relevant="additions text">
            <p class="ai-assistant-welcome">{{ __('I can explain your options and next steps. I cannot approve applications or move money.') }}</p>
        </div>
        <form class="ai-assistant-form" data-ai-form>
            @csrf
            <label class="sr-only" for="credai-assistant-input">{{ __('Your message') }}</label>
            <input id="credai-assistant-input" data-ai-input name="message" type="text" maxlength="2000" autocomplete="off" placeholder="{{ __('Ask a question...') }}" required>
            <button type="submit" class="ai-assistant-send" data-ai-send aria-label="{{ __('Send message') }}" title="{{ __('Send message') }}">{{ __('Send') }}</button>
        </form>
        <p class="ai-assistant-privacy">{{ __('Do not share passwords, PINs, OTPs, or private account and medical details.') }}</p>
    </section>
</div>

<script>
    (() => {
        const labels = {
            thinking: @js(__('Thinking')),
            send: @js(__('Send')),
            failed: @js(__('The assistant could not respond. Please try again.')),
        };

        document.querySelectorAll('[data-ai-assistant]').forEach((widget) => {
            const panel = widget.querySelector('[data-ai-panel]');
            const launch = widget.querySelector('[data-ai-launch]');
            const close = widget.querySelector('[data-ai-close]');
            const form = widget.querySelector('[data-ai-form]');
            const input = widget.querySelector('[data-ai-input]');
            const send = widget.querySelector('[data-ai-send]');
            const messages = widget.querySelector('[data-ai-messages]');
            const history = [];

            const addMessage = (role, text) => {
                const row = document.createElement('div');
                row.className = `ai-assistant-message ai-assistant-message--${role}`;
                const bubble = document.createElement('p');
                bubble.textContent = text;
                row.append(bubble);
                messages.append(row);
                messages.scrollTop = messages.scrollHeight;
            };

            if (launch) {
                launch.addEventListener('click', () => {
                    panel.hidden = false;
                    launch.setAttribute('aria-expanded', 'true');
                    input.focus();
                });
            }

            if (close) {
                close.addEventListener('click', () => {
                    panel.hidden = true;
                    launch.setAttribute('aria-expanded', 'false');
                    launch.focus();
                });
            }

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const message = input.value.trim();

                if (!message || send.disabled) {
                    return;
                }

                addMessage('user', message);
                input.value = '';
                input.disabled = true;
                send.disabled = true;
                send.textContent = labels.thinking;

                try {
                    const response = await fetch(widget.dataset.endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            message,
                            page: widget.dataset.page,
                            history: history.slice(-8),
                        }),
                    });
                    const data = await response.json();
                    const assistantResponse = typeof data.response === 'string'
                        ? data.response
                        : data.message;

                    if (!response.ok || typeof assistantResponse !== 'string') {
                        throw new Error('The assistant request failed.');
                    }

                    addMessage('assistant', assistantResponse);

                    if (data.success !== false) {
                        history.push({ role: 'user', content: message }, { role: 'assistant', content: assistantResponse });
                    }
                } catch {
                    addMessage('assistant', labels.failed);
                } finally {
                    input.disabled = false;
                    send.disabled = false;
                    send.textContent = labels.send;
                    input.focus();
                }
            });
        });
    })();
</script>
