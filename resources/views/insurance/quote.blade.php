<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="eyebrow">Smart protection</p>
                <h1 class="page-title">Generate a policy quote</h1>
                <p class="page-subtitle">{{ __('Choose a cover type and get a premium quote. Use a synced phone balance or enter an estimate for direct payment.') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="form-shell">
        @if($errors->any())<div class="workspace-error">{{ $errors->first() }}</div>@endif
        @auth
            @if(in_array(auth()->user()->role, ['individual', 'business'], true))
                <section class="surface-card mb-6">
                    <div class="section-heading">
                        <div>
                            <span class="eyebrow">{{ __('Available mobile-money balance') }}</span>
                            <h3>{{ number_format($verifiedBalance['balance'], 2) }} TZS</h3>
                            <p class="empty-copy">{{ $verifiedBalance['transaction_count'] > 0
                                ? __('Using :count verified transactions, less pending requests.', ['count' => $verifiedBalance['transaction_count']])
                                : __('You can quote and pay directly now. Sync transactions to use a verified balance or apply for a loan.') }}</p>
                        </div>
                        <form method="POST" action="{{ route('transactions.sync') }}">
                            @csrf
                            <button type="submit" class="button button--green">{{ __('Sync transactions') }}</button>
                        </form>
                    </div>
                </section>
            @endif
        @endauth
        <div class="form-intro">
            <span class="eyebrow">Balance-based protection</span>
            <h2>Tell us about the customer and the cover they need.</h2>
            <p>{{ __('Use a verified transaction balance when you have synced your phone, or enter an estimate to pay directly. Loan applications still require verified transaction history.') }}</p>
        </div>

        <form class="workspace-form" id="insurance-quote-form">
            @csrf
            <div class="form-row">
                <div class="form-field">
                    <label for="name">Full name</label>
                    <input id="name" name="name" type="text" placeholder="Amina Mjanja" required>
                </div>
                <div class="form-field">
                    <label for="phone">{{ __('Mobile money phone number') }}</label>
                    <input id="phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone', auth()->user()?->phone) }}" placeholder="+255712345678" required>
                </div>
            </div>

            <div class="form-row">
                @if(!auth()->check() || !in_array(auth()->user()->role, ['individual', 'business'], true))
                    <div class="form-field">
                        <label for="sim_balance">SIM account balance (TZS)</label>
                        <input id="sim_balance" name="sim_balance" type="number" min="0" step="1000" placeholder="250000" required>
                    </div>
                @elseif($verifiedBalance['transaction_count'] > 0)
                    <input id="sim_balance" name="sim_balance" type="hidden" value="{{ $verifiedBalance['balance'] }}">
                    <div class="form-field"><span class="eyebrow">{{ __('Verified mobile-money balance') }}</span><strong>{{ number_format($verifiedBalance['balance'], 2) }} TZS</strong></div>
                @else
                    <div class="form-field">
                        <label for="sim_balance">{{ __('Estimated mobile-money balance (TZS)') }}</label>
                        <input id="sim_balance" name="sim_balance" type="number" min="0" step="1000" placeholder="250000" required>
                        <small>{{ __('A verified phone transaction sync is required for a loan, but not for direct payment.') }}</small>
                    </div>
                @endif
                <div class="form-field">
                    <label for="coverage_goal">Coverage goal</label>
                    <select id="coverage_goal" name="coverage_goal">
                        <option value="family_protection">Family protection</option>
                        <option value="health_support">Health support</option>
                        <option value="business_cover">Business cover</option>
                        <option value="travel_guard">Travel guard</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('dashboard') }}" class="form-cancel">Back to dashboard</a>
                <button type="submit" class="button button--green">Generate quote</button>
            </div>
        </form>

        <div class="surface-card mt-6" id="quote-result" style="padding: 24px; display: none;">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">{{ __('Quote explanation') }}</span>
                    <h3>Your quote</h3>
                </div>
            </div>
            <div class="ai-panel">
                    <span class="ai-badge" id="quote-ai-badge">{{ __('Rules-based preview') }}</span>
                <p id="ai-summary">Your quote is ready.</p>
            </div>
            <p class="empty-copy">{{ __('The CredHealth annual plan costs TZS :premium per year and provides TZS :cover in annual cover. The figures below are estimates based on your balance.', ['premium' => number_format((float) config('insurance.annual_premium', 50400), 0), 'cover' => number_format((float) config('insurance.annual_coverage', 100000), 0)]) }}</p>
            <ul class="ai-list">
                <li><strong>{{ __('Estimated monthly premium') }}</strong><span id="quote-premium">0 TZS</span></li>
                <li><strong>{{ __('Estimated coverage') }}</strong><span id="quote-coverage">0 TZS</span></li>
                <li><strong>Risk</strong><span id="quote-risk">-</span></li>
                <li><strong>Plan</strong><span id="quote-plan">-</span></li>
            </ul>
        </div>

        @if(auth()->check() && in_array(auth()->user()->role, ['individual', 'business'], true))
            <div class="content-grid mt-6" id="checkout-actions" style="display: none;">
                <section class="surface-card">
                    <div class="section-heading"><div><span class="eyebrow">{{ __('Direct payment') }}</span><h3>{{ __('Pay from mobile money') }}</h3></div></div>
                    <p class="empty-copy">{{ __('Choose an instant phone prompt or get a control number and pay it from your mobile-money menu. Coverage starts only after ClickPesa confirms the payment.') }}</p>
                    <form method="POST" action="{{ route('insurance.payments.store') }}" class="workspace-form checkout-form">
                        @csrf
                        <input type="hidden" name="phone" id="payment-phone">
                        <p>{{ __('Premium to pay') }}: <strong id="payment-premium">—</strong></p>
                        <div class="form-field">
                            <label for="collection_method">{{ __('Mobile-money payment method') }}</label>
                            <select id="collection_method" name="collection_method" required>
                                <option value="ussd">{{ __('USSD phone prompt') }}</option>
                                <option value="control_number">{{ __('Control number (pay from mobile-money menu)') }}</option>
                            </select>
                        </div>
                        <button type="submit" class="button button--green">{{ __('Continue to mobile-money payment') }}</button>
                    </form>
                </section>
                <section class="surface-card">
                    <div class="section-heading"><div><span class="eyebrow">{{ __('Loan option') }}</span><h3>{{ __('Apply for premium financing') }}</h3></div></div>
                    <p class="empty-copy">{{ __('Financing is for the annual CredHealth plan. Sync verified transactions first. An insurer-directed loan creates a pending payment after approval; coverage starts only after ClickPesa confirms payment.') }}</p>
                    <form method="POST" action="{{ route('insurance.loans.store') }}" class="workspace-form checkout-form">
                        @csrf
                        <p>{{ __('Financing amount') }}: <strong id="loan-premium">—</strong></p>
                        <div class="form-field"><label for="disbursement_destination">{{ __('Where should the loan be disbursed?') }}</label><select id="disbursement_destination" name="disbursement_destination" required><option value="insurer">{{ __('Pay the insurer directly') }}</option><option value="customer">{{ __('Disburse to my mobile-money account') }}</option></select></div>
                        <div class="form-field" id="disbursement-phone-field" hidden><label for="disbursement_phone">{{ __('Mobile-money phone number') }}</label><input id="disbursement_phone" name="disbursement_phone" type="tel" maxlength="30"></div>
                        <button type="submit" class="button button--green">{{ __('Submit loan application') }}</button>
                    </form>
                </section>
            </div>
        @elseif(!auth()->check())
            <p class="workspace-status mt-6" id="checkout-login-prompt" style="display: none;">{{ __('Sign in to choose a payment or loan option.') }} <a href="{{ route('login') }}">{{ __('Log in') }}</a></p>
        @else
            <p class="workspace-status mt-6">{{ __('Payment and premium-financing requests are available to customer accounts.') }}</p>
        @endif
    </div>

    <div id="quote-error" class="workspace-error" role="alert" hidden></div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('insurance-quote-form');
            const result = document.getElementById('quote-result');
            const quoteError = document.getElementById('quote-error');
            const checkoutActions = document.getElementById('checkout-actions');
            const checkoutLoginPrompt = document.getElementById('checkout-login-prompt');
            const disbursementDestination = document.getElementById('disbursement_destination');
            const disbursementPhoneField = document.getElementById('disbursement-phone-field');

            if (disbursementDestination) {
                disbursementDestination.addEventListener('change', function () {
                    const sendsToCustomer = this.value === 'customer';
                    disbursementPhoneField.hidden = !sendsToCustomer;
                    document.getElementById('disbursement_phone').required = sendsToCustomer;
                });
            }

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                quoteError.hidden = true;

                try {
                    const response = await fetch('{{ route('insurance.quote.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: new FormData(form),
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        const validationMessage = Object.values(data.errors ?? {})[0]?.[0];
                        throw new Error(data.message ?? validationMessage ?? '{{ __('Could not generate quote. Please try again.') }}');
                    }

                    document.getElementById('ai-summary').textContent = data.ai_summary;
                    document.getElementById('quote-ai-badge').textContent = data.ai_status === 'ready' ? 'AI' : '{{ __('Rules-based preview') }}';
                    document.getElementById('quote-premium').textContent = new Intl.NumberFormat('en-US').format(data.monthly_premium) + ' TZS';
                    document.getElementById('quote-coverage').textContent = new Intl.NumberFormat('en-US').format(data.coverage_amount) + ' TZS';
                    document.getElementById('quote-risk').textContent = data.risk_level;
                    document.getElementById('quote-plan').textContent = data.recommended_plan;
                    if (checkoutActions) {
                        document.getElementById('payment-phone').value = document.getElementById('phone').value;
                        document.getElementById('payment-premium').textContent = new Intl.NumberFormat('en-US').format({{ (int) config('insurance.annual_premium', 50400) }}) + ' TZS / year';
                        document.getElementById('loan-premium').textContent = new Intl.NumberFormat('en-US').format({{ (int) config('insurance.annual_premium', 50400) }}) + ' TZS / year';
                        document.getElementById('disbursement_phone').value = document.getElementById('phone').value;
                        checkoutActions.style.display = 'grid';
                    }
                    if (checkoutLoginPrompt) {
                        checkoutLoginPrompt.style.display = 'block';
                    }
                    result.style.display = 'block';
                } catch (error) {
                    quoteError.textContent = error instanceof Error
                        ? error.message
                        : '{{ __('Could not generate quote. Please try again.') }}';
                    quoteError.hidden = false;
                }
            });
        });
    </script>
</x-app-layout>
