<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="eyebrow">Smart protection</p>
                <h1 class="page-title">Generate a policy quote</h1>
                <p class="page-subtitle">Check the SIM balance, choose a cover type, and let AI recommend the best monthly premium.</p>
            </div>
        </div>
    </x-slot>

    <div class="form-shell">
        @if($errors->any())<div class="workspace-error">{{ $errors->first() }}</div>@endif
        <div class="form-intro">
            <span class="eyebrow">Balance-based protection</span>
            <h2>Tell us about the customer and the cover they need.</h2>
            <p>The platform reads the available mobile-money balance and estimates a fair premium for the selected protection goal.</p>
        </div>

        <form class="workspace-form" id="insurance-quote-form">
            @csrf
            <div class="form-row">
                <div class="form-field">
                    <label for="name">Full name</label>
                    <input id="name" name="name" type="text" placeholder="Amina Mjanja" required>
                </div>
                <div class="form-field">
                    <label for="phone">SIM phone number</label>
                    <input id="phone" name="phone" type="text" placeholder="+255712345678" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="sim_balance">SIM account balance (TZS)</label>
                    <input id="sim_balance" name="sim_balance" type="number" min="0" step="1000" placeholder="250000" required>
                </div>
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
            <ul class="ai-list">
                <li><strong>Premium</strong><span id="quote-premium">0 TZS</span></li>
                <li><strong>Coverage</strong><span id="quote-coverage">0 TZS</span></li>
                <li><strong>Risk</strong><span id="quote-risk">-</span></li>
                <li><strong>Plan</strong><span id="quote-plan">-</span></li>
            </ul>
        </div>

        @auth
            <div class="content-grid mt-6" id="checkout-actions" style="display: none;">
                <section class="surface-card">
                    <div class="section-heading"><div><span class="eyebrow">{{ __('Direct payment') }}</span><h3>{{ __('Pay from mobile money') }}</h3></div></div>
                    <p class="empty-copy">{{ __('This creates a payment request. The charge stays pending until the mobile-money API confirms it.') }}</p>
                    <form method="POST" action="{{ route('insurance.payments.store') }}" class="workspace-form checkout-form">
                        @csrf
                        <input type="hidden" name="phone" id="payment-phone">
                        <div class="form-field"><label for="mobile_money_provider">{{ __('Mobile-money network') }}</label><select id="mobile_money_provider" name="mobile_money_provider" required><option value="airtel_money">Airtel Money</option><option value="mpesa">M-Pesa</option><option value="tigo_pesa">Tigo Pesa</option><option value="halopesa">HaloPesa</option></select></div>
                        <button type="submit" class="button button--green">{{ __('Create payment request') }}</button>
                    </form>
                </section>
                <section class="surface-card">
                    <div class="section-heading"><div><span class="eyebrow">{{ __('Loan option') }}</span><h3>{{ __('Apply for premium financing') }}</h3></div></div>
                    <p class="empty-copy">{{ __('Loan approval requires a verified mobile-money balance and administrator review.') }}</p>
                    <form method="POST" action="{{ route('insurance.loans.store') }}" class="workspace-form checkout-form">
                        @csrf
                        <div class="form-field"><label for="requested_amount">{{ __('Requested amount (TZS)') }}</label><input id="requested_amount" name="requested_amount" type="number" min="1000" max="10000000" step="1000" required></div>
                        <div class="form-field"><label for="disbursement_destination">{{ __('Where should the loan be disbursed?') }}</label><select id="disbursement_destination" name="disbursement_destination" required><option value="insurer">{{ __('Pay the insurer directly') }}</option><option value="customer">{{ __('Disburse to my mobile-money account') }}</option></select></div>
                        <div class="form-field" id="disbursement-phone-field" hidden><label for="disbursement_phone">{{ __('Mobile-money phone number') }}</label><input id="disbursement_phone" name="disbursement_phone" type="tel" maxlength="30"></div>
                        <button type="submit" class="button button--green">{{ __('Submit loan application') }}</button>
                    </form>
                </section>
            </div>
        @else
            <p class="workspace-status mt-6" id="checkout-login-prompt" style="display: none;">{{ __('Sign in to choose a payment or loan option.') }} <a href="{{ route('login') }}">{{ __('Log in') }}</a></p>
        @endauth
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('insurance-quote-form');
            const result = document.getElementById('quote-result');
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

                const formData = new FormData(form);
                const response = await fetch('{{ route('insurance.quote.store') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await response.json();
                document.getElementById('ai-summary').textContent = data.ai_summary;
                document.getElementById('quote-ai-badge').textContent = data.ai_status === 'ready' ? 'AI' : '{{ __('Rules-based preview') }}';
                document.getElementById('quote-premium').textContent = new Intl.NumberFormat('en-US').format(data.monthly_premium) + ' TZS';
                document.getElementById('quote-coverage').textContent = new Intl.NumberFormat('en-US').format(data.coverage_amount) + ' TZS';
                document.getElementById('quote-risk').textContent = data.risk_level;
                document.getElementById('quote-plan').textContent = data.recommended_plan;
                if (checkoutActions) {
                    document.getElementById('payment-phone').value = document.getElementById('phone').value;
                    document.getElementById('requested_amount').value = data.monthly_premium;
                    document.getElementById('disbursement_phone').value = document.getElementById('phone').value;
                    checkoutActions.style.display = 'grid';
                }
                if (checkoutLoginPrompt) {
                    checkoutLoginPrompt.style.display = 'block';
                }
                result.style.display = 'block';
            });
        });
    </script>
</x-app-layout>
