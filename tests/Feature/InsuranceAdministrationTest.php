<?php

use App\Models\InsuranceLoanApplication;
use App\Models\InsurancePayment;
use App\Models\InsuranceProfile;
use App\Models\InsuranceProvider;
use App\Models\MobileMoneyTransaction;
use App\Models\ProviderService;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('insurer sees the provider dashboard', function () {
    $providerUser = User::factory()->create(['role' => 'insurer']);

    $response = $this->actingAs($providerUser)->get(route('provider.dashboard'));

    $response->assertOk();
    $response->assertSee(__('Provider workspace'));
    $response->assertSee(__('No assigned policies yet.'));
});

test('administrator sees the insurance management dashboard', function () {
    $administrator = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($administrator)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee(__('Administration overview'));
    $response->assertSee(route('admin.providers'));
});

test('administrator sections render on their own pages', function (string $routeName, string $heading) {
    $administrator = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($administrator)->get(route($routeName));

    $response->assertOk();
    $response->assertSee($heading);
    $response->assertSee('aria-current="page"', false);
})->with([
    'overview' => ['admin.dashboard', 'Administration overview'],
    'providers' => ['admin.providers', 'Insurance providers'],
    'policies' => ['admin.policies', 'Insurance policies'],
    'claims' => ['admin.claims', 'Claims'],
    'payments' => ['admin.payments', 'Payment requests'],
    'loans' => ['admin.loans', 'Loan applications'],
    'activity log' => ['admin.activity', 'Activity log'],
]);

test('non administrators cannot access admin section pages', function () {
    $user = User::factory()->create(['role' => 'individual']);

    $response = $this->actingAs($user)->get(route('admin.providers'));

    $response->assertForbidden();
});

test('admin can search and filter providers on the provider page', function () {
    $administrator = User::factory()->create(['role' => 'admin']);
    $pendingOwner = User::factory()->create(['role' => 'insurer']);
    $approvedOwner = User::factory()->create(['role' => 'insurer']);
    $pendingProvider = InsuranceProvider::create([
        'user_id' => $pendingOwner->id,
        'organization_name' => 'Bahari Cover',
        'license_number' => 'TZ-INS-601',
        'provider_type' => 'insurer',
        'status' => 'pending',
    ]);
    InsuranceProvider::create([
        'user_id' => $approvedOwner->id,
        'organization_name' => 'Bahari Life',
        'license_number' => 'TZ-INS-602',
        'provider_type' => 'insurer',
        'status' => 'approved',
    ]);

    $response = $this->actingAs($administrator)->get(route('admin.providers', [
        'search' => 'Bahari',
        'status' => 'pending',
    ]));

    $response->assertOk();
    $response->assertSee('Bahari Cover');
    $response->assertDontSee('Bahari Life');
    $response->assertSee(route('admin.insurance-providers.status', $pendingProvider));
});

test('public registration cannot assign the admin role', function () {
    $response = $this->post(route('register'), [
        'name' => 'Unexpected Admin',
        'email' => 'unexpected-admin@example.com',
        'role' => 'admin',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasErrors('role');
    $this->assertDatabaseMissing('users', ['email' => 'unexpected-admin@example.com']);
});

test('administrator can approve an insurance provider and the change is audited', function () {
    $administrator = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create(['role' => 'insurer']);
    $provider = InsuranceProvider::create([
        'user_id' => $owner->id,
        'organization_name' => 'Upendo Assurance',
        'license_number' => 'TZ-INS-101',
        'provider_type' => 'insurer',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($administrator)->patch(route('admin.insurance-providers.status', $provider), [
        'status' => 'approved',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('insurance_providers', ['id' => $provider->id, 'status' => 'approved']);
    $this->assertDatabaseHas('audit_events', [
        'actor_id' => $administrator->id,
        'event' => 'insurance_provider.status_updated',
        'auditable_id' => $provider->id,
    ]);
});

test('non administrators cannot change insurance provider status', function () {
    $user = User::factory()->create(['role' => 'individual']);
    $owner = User::factory()->create(['role' => 'insurer']);
    $provider = InsuranceProvider::create([
        'user_id' => $owner->id,
        'organization_name' => 'Upendo Assurance',
        'license_number' => 'TZ-INS-102',
        'provider_type' => 'insurer',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->patch(route('admin.insurance-providers.status', $provider), [
        'status' => 'approved',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('insurance_providers', ['id' => $provider->id, 'status' => 'pending']);
});

test('insurer details are submitted as pending until administrator review', function () {
    $providerUser = User::factory()->create(['role' => 'insurer']);

    $response = $this->actingAs($providerUser)->put(route('provider.profile.store'), [
        'organization_name' => 'Bahari Insurance',
        'license_number' => 'TZ-INS-204',
    ]);

    $response->assertRedirect(route('provider.dashboard'));
    $this->assertDatabaseHas('insurance_providers', [
        'user_id' => $providerUser->id,
        'organization_name' => 'Bahari Insurance',
        'license_number' => 'TZ-INS-204',
        'status' => 'pending',
    ]);
});

test('customer can start a ClickPesa checkout for their own quote', function () {
    config([
        'services.clickpesa.client_id' => 'test-client',
        'services.clickpesa.api_key' => 'test-api-key',
        'services.clickpesa.api_url' => 'https://api.clickpesa.com',
    ]);
    Http::fake([
        '*api.clickpesa.com/third-parties/generate-token' => Http::response(['token' => 'Bearer test-token']),
        '*api.clickpesa.com/third-parties/payments/preview-ussd-push-request' => Http::response([
            'activeMethods' => [['name' => 'M-PESA', 'status' => 'AVAILABLE']],
        ]),
        '*api.clickpesa.com/third-parties/payments/initiate-ussd-push-request' => fn ($request) => Http::response([
            'id' => 'CLICKPESA-TEST-ID',
            'status' => 'PROCESSING',
            'orderReference' => $request['orderReference'],
        ]),
    ]);
    $customer = User::factory()->create(['role' => 'individual', 'phone' => '+255712345678']);
    $profile = InsuranceProfile::create([
        'user_id' => $customer->id,
        'full_name' => $customer->name,
        'phone' => '+255712345678',
        'sim_balance' => 250000,
        'coverage_goal' => 'family_protection',
        'monthly_premium' => 3500,
        'coverage_amount' => 700000,
    ]);
    MobileMoneyTransaction::create([
        'user_id' => $customer->id,
        'reference' => 'balance-credit-301',
        'amount' => 5000,
        'transaction_type' => 'credit',
        'occurred_at' => now(),
        'verification_status' => 'verified',
    ]);

    $response = $this->actingAs($customer)->post(route('insurance.payments.store'), [
        'phone' => '+255712345678',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('insurance_payments', [
        'user_id' => $customer->id,
        'insurance_profile_id' => $profile->id,
        'amount' => 50400,
        'status' => 'processing',
        'mobile_money_provider' => null,
    ]);

    $secondRequest = $this->actingAs($customer)
        ->from(route('dashboard'))
        ->post(route('insurance.payments.store'), [
            'phone' => '+255712345678',
        ]);

    $secondRequest->assertRedirect(route('dashboard'));
    $secondRequest->assertSessionHasErrors('phone');
    $this->assertDatabaseCount('insurance_payments', 1);
});

test('generating a customer quote uses the verified transaction balance', function () {
    $customer = User::factory()->create(['role' => 'individual', 'phone' => '+255712345678']);
    $profile = InsuranceProfile::create([
        'user_id' => $customer->id,
        'full_name' => $customer->name,
        'phone' => '+255712345678',
        'sim_balance' => 250000,
        'coverage_goal' => 'family_protection',
        'monthly_premium' => 3500,
        'coverage_amount' => 700000,
    ]);
    $profile->forceFill(['balance_verified_at' => now()])->save();
    MobileMoneyTransaction::create([
        'user_id' => $customer->id,
        'reference' => 'quote-credit-302',
        'amount' => 12000,
        'transaction_type' => 'credit',
        'occurred_at' => now(),
        'verification_status' => 'verified',
    ]);

    $response = $this->actingAs($customer)->postJson(route('insurance.quote.store'), [
        'name' => $customer->name,
        'phone' => '+255712345678',
        'sim_balance' => 9999999,
        'coverage_goal' => 'family_protection',
    ]);

    $response->assertOk();
    $response->assertJsonPath('sim_balance', 12000);
    $this->assertDatabaseHas('insurance_profiles', [
        'id' => $profile->id,
        'sim_balance' => 12000,
    ]);
});

test('customer can apply for a loan disbursed to their mobile-money account', function () {
    $customer = User::factory()->create(['role' => 'individual']);
    $profile = InsuranceProfile::create([
        'user_id' => $customer->id,
        'full_name' => $customer->name,
        'phone' => '+255712345678',
        'sim_balance' => 250000,
        'coverage_goal' => 'family_protection',
        'monthly_premium' => 3500,
        'coverage_amount' => 700000,
    ]);
    MobileMoneyTransaction::create([
        'user_id' => $customer->id,
        'reference' => 'loan-credit-303',
        'amount' => 250000,
        'transaction_type' => 'credit',
        'occurred_at' => now(),
        'verification_status' => 'verified',
    ]);

    $response = $this->actingAs($customer)->post(route('insurance.loans.store'), [
        'requested_amount' => 10000,
        'disbursement_destination' => 'customer',
        'disbursement_phone' => '+255712345678',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('insurance_loan_applications', [
        'user_id' => $customer->id,
        'insurance_profile_id' => $profile->id,
        'requested_amount' => 50400,
        'sim_balance_at_application' => 250000,
        'disbursement_destination' => 'customer',
        'disbursement_phone' => '+255712345678',
        'status' => 'submitted',
        'balance_verified_at_application' => null,
    ]);
});

test('business customer can pay a quoted premium within their verified transaction balance', function () {
    config([
        'services.clickpesa.client_id' => 'test-client',
        'services.clickpesa.api_key' => 'test-api-key',
        'services.clickpesa.api_url' => 'https://api.clickpesa.com',
    ]);
    Http::fake([
        '*api.clickpesa.com/third-parties/generate-token' => Http::response(['token' => 'Bearer test-token']),
        '*api.clickpesa.com/third-parties/payments/preview-ussd-push-request' => Http::response([
            'activeMethods' => [['name' => 'M-PESA', 'status' => 'AVAILABLE']],
        ]),
        '*api.clickpesa.com/third-parties/payments/initiate-ussd-push-request' => fn ($request) => Http::response([
            'id' => 'CLICKPESA-TEST-ID',
            'status' => 'PROCESSING',
            'orderReference' => $request['orderReference'],
        ]),
    ]);
    $businessCustomer = User::factory()->create([
        'role' => 'business',
        'phone' => '+255712345678',
    ]);
    $profile = InsuranceProfile::create([
        'user_id' => $businessCustomer->id,
        'full_name' => $businessCustomer->name,
        'phone' => $businessCustomer->phone,
        'sim_balance' => 10000,
        'coverage_goal' => 'family_protection',
        'monthly_premium' => 3500,
        'coverage_amount' => 700000,
    ]);
    MobileMoneyTransaction::create([
        'user_id' => $businessCustomer->id,
        'reference' => 'business-credit-304',
        'amount' => 8000,
        'transaction_type' => 'credit',
        'occurred_at' => now(),
        'verification_status' => 'verified',
    ]);
    MobileMoneyTransaction::create([
        'user_id' => $businessCustomer->id,
        'reference' => 'business-debit-304',
        'amount' => 3000,
        'transaction_type' => 'debit',
        'occurred_at' => now(),
        'verification_status' => 'verified',
    ]);

    $response = $this->actingAs($businessCustomer)->post(route('insurance.payments.store'), [
        'phone' => $businessCustomer->phone,
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('insurance_payments', [
        'user_id' => $businessCustomer->id,
        'insurance_profile_id' => $profile->id,
        'amount' => 50400,
        'status' => 'processing',
    ]);
});

test('business customer can apply for premium financing within their net transaction balance', function () {
    $businessCustomer = User::factory()->create([
        'role' => 'business',
        'phone' => '+255712345678',
    ]);
    $profile = InsuranceProfile::create([
        'user_id' => $businessCustomer->id,
        'full_name' => $businessCustomer->name,
        'phone' => $businessCustomer->phone,
        'sim_balance' => 10000,
        'coverage_goal' => 'family_protection',
        'monthly_premium' => 3500,
        'coverage_amount' => 700000,
    ]);
    MobileMoneyTransaction::create([
        'user_id' => $businessCustomer->id,
        'reference' => 'business-loan-credit-310',
        'amount' => 80000,
        'transaction_type' => 'credit',
        'occurred_at' => now(),
        'verification_status' => 'verified',
    ]);
    MobileMoneyTransaction::create([
        'user_id' => $businessCustomer->id,
        'reference' => 'business-loan-debit-310',
        'amount' => 3000,
        'transaction_type' => 'debit',
        'occurred_at' => now(),
        'verification_status' => 'verified',
    ]);

    $response = $this->actingAs($businessCustomer)->post(route('insurance.loans.store'), [
        'disbursement_destination' => 'insurer',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('insurance_loan_applications', [
        'user_id' => $businessCustomer->id,
        'insurance_profile_id' => $profile->id,
        'requested_amount' => 50400,
        'sim_balance_at_application' => 77000,
        'status' => 'submitted',
    ]);
});

test('business customer can sync verified mobile-money transactions for balance eligibility', function () {
    $businessCustomer = User::factory()->create([
        'role' => 'business',
        'phone' => '+255712345678',
    ]);
    config([
        'services.transaction_api.url' => 'https://mobile-money.test/transactions',
        'services.transaction_api.token' => 'test-token',
    ]);
    Http::fake([
        'https://mobile-money.test/*' => Http::response([
            'transactions' => [
                [
                    'reference' => 'sync-credit-311',
                    'amount' => 12000,
                    'type' => 'credit',
                    'occurred_at' => '2026-10-05T09:00:00+03:00',
                    'provider' => 'M-Pesa',
                ],
                [
                    'reference' => 'sync-debit-311',
                    'amount' => 2000,
                    'type' => 'debit',
                    'occurred_at' => '2026-10-05T10:00:00+03:00',
                    'provider' => 'M-Pesa',
                ],
            ],
        ]),
    ]);

    $response = $this->actingAs($businessCustomer)->post(route('transactions.sync'));

    $response->assertRedirect();
    expect(session('status'))->toContain('Available balance: TZS 10,000.00');
    $this->assertDatabaseHas('mobile_money_transactions', [
        'user_id' => $businessCustomer->id,
        'reference' => 'sync-credit-311',
        'amount' => 12000,
        'verification_status' => 'verified',
    ]);
    $this->assertDatabaseHas('mobile_money_transactions', [
        'user_id' => $businessCustomer->id,
        'reference' => 'sync-debit-311',
        'amount' => 2000,
        'verification_status' => 'verified',
    ]);
    $this->assertDatabaseHas('audit_events', [
        'actor_id' => $businessCustomer->id,
        'event' => 'mobile_money.transactions_synced',
    ]);
});

test('customers see only their own verified mobile-money transaction history', function () {
    $customer = User::factory()->create(['role' => 'business']);
    $otherCustomer = User::factory()->create(['role' => 'individual']);
    MobileMoneyTransaction::create([
        'user_id' => $customer->id,
        'reference' => 'owned-mobile-transaction-315',
        'amount' => 10000,
        'transaction_type' => 'credit',
        'occurred_at' => now(),
        'verification_status' => 'verified',
    ]);
    MobileMoneyTransaction::create([
        'user_id' => $otherCustomer->id,
        'reference' => 'private-mobile-transaction-315',
        'amount' => 90000,
        'transaction_type' => 'credit',
        'occurred_at' => now(),
        'verification_status' => 'verified',
    ]);

    $response = $this->actingAs($customer)->get(route('transactions.mobile-money.index'));

    $response->assertOk();
    $response->assertSee('owned-mobile-transaction-315');
    $response->assertDontSee('private-mobile-transaction-315');
});

test('confirmed payments remain reserved until their debit appears in verified transactions', function () {
    $customer = User::factory()->create(['role' => 'individual']);
    MobileMoneyTransaction::create([
        'user_id' => $customer->id,
        'reference' => 'balance-credit-316',
        'amount' => 5000,
        'transaction_type' => 'credit',
        'occurred_at' => now(),
        'verification_status' => 'verified',
    ]);
    InsurancePayment::create([
        'user_id' => $customer->id,
        'reference' => 'paid-premium-316',
        'provider_reference' => 'MOBILE-RECEIPT-316',
        'phone' => '+255712345678',
        'amount' => 3500,
        'status' => 'paid',
    ]);

    $beforeSync = $this->actingAs($customer)->get(route('transactions.mobile-money.index'));

    $beforeSync->assertSee('TZS 1,500.00');

    MobileMoneyTransaction::create([
        'user_id' => $customer->id,
        'reference' => 'MOBILE-RECEIPT-316',
        'amount' => 3500,
        'transaction_type' => 'debit',
        'occurred_at' => now(),
        'verification_status' => 'verified',
    ]);

    $afterSync = $this->actingAs($customer)->get(route('transactions.mobile-money.index'));

    $afterSync->assertSee('TZS 1,500.00');
});

test('approved hospital can publish a service and a business customer can pay through ClickPesa', function () {
    config([
        'services.clickpesa.client_id' => 'test-client',
        'services.clickpesa.api_key' => 'test-api-key',
        'services.clickpesa.api_url' => 'https://api.clickpesa.com',
    ]);
    Http::fake([
        '*api.clickpesa.com/third-parties/generate-token' => Http::response(['token' => 'Bearer test-token']),
        '*api.clickpesa.com/third-parties/payments/preview-ussd-push-request' => Http::response([
            'activeMethods' => [['name' => 'M-PESA', 'status' => 'AVAILABLE']],
        ]),
        '*api.clickpesa.com/third-parties/payments/initiate-ussd-push-request' => fn ($request) => Http::response([
            'id' => 'CLICKPESA-TEST-ID',
            'status' => 'PROCESSING',
            'orderReference' => $request['orderReference'],
        ]),
    ]);
    $hospital = User::factory()->create(['role' => 'hospital']);
    $hospitalProvider = InsuranceProvider::create([
        'user_id' => $hospital->id,
        'organization_name' => 'Upendo Hospital',
        'license_number' => 'HOSP-312',
        'provider_type' => 'hospital',
        'status' => 'approved',
    ]);
    $customer = User::factory()->create([
        'role' => 'business',
        'phone' => '+255712345678',
    ]);
    MobileMoneyTransaction::create([
        'user_id' => $customer->id,
        'reference' => 'service-balance-312',
        'amount' => 10000,
        'transaction_type' => 'credit',
        'occurred_at' => now(),
        'verification_status' => 'verified',
    ]);

    $publishResponse = $this->actingAs($hospital)->post(route('provider.services.store'), [
        'name' => 'General consultation',
        'description' => 'Outpatient consultation',
        'price' => 5000,
    ]);

    $publishResponse->assertRedirect();
    $this->assertDatabaseHas('provider_services', [
        'insurance_provider_id' => $hospitalProvider->id,
        'name' => 'General consultation',
        'price' => 5000,
        'is_active' => true,
    ]);
    $service = ProviderService::where('insurance_provider_id', $hospitalProvider->id)->firstOrFail();

    $catalogResponse = $this->actingAs($customer)->get(route('insurance.services.index'));

    $catalogResponse->assertOk();
    $catalogResponse->assertSee('General consultation');
    $catalogResponse->assertSee('Upendo Hospital');

    $paymentResponse = $this->actingAs($customer)->post(route('insurance.services.pay', $service), [
        'phone' => $customer->phone,
        'mobile_money_provider' => 'mpesa',
        'amount' => 1,
    ]);

    $paymentResponse->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('insurance_payments', [
        'user_id' => $customer->id,
        'provider_service_id' => $service->id,
        'payment_type' => 'provider_service',
        'amount' => 5000,
        'status' => 'processing',
    ]);
    $this->assertDatabaseHas('audit_events', [
        'actor_id' => $customer->id,
        'event' => 'provider_service.payment_requested',
    ]);

    $providerDashboard = $this->actingAs($hospital)->get(route('provider.dashboard'));

    $providerDashboard->assertOk();
    $providerDashboard->assertSee($customer->name);
    $providerDashboard->assertSee(__('Processing'));

    $payment = InsurancePayment::where('provider_service_id', $service->id)->firstOrFail();
    $administrator = User::factory()->create(['role' => 'admin']);
    $confirmationResponse = $this->actingAs($administrator)->patch(route('admin.payments.status', $payment), [
        'status' => 'paid',
        'provider_reference' => 'HOSPITAL-RECEIPT-312',
    ]);

    $confirmationResponse->assertRedirect();
    $this->assertDatabaseHas('insurance_payments', [
        'id' => $payment->id,
        'status' => 'paid',
        'provider_reference' => 'HOSPITAL-RECEIPT-312',
    ]);

    $providerDashboard = $this->actingAs($hospital)->get(route('provider.dashboard'));
    $providerDashboard->assertSee(__('Paid'));
    $providerDashboard->assertSee('HOSPITAL-RECEIPT-312');

    $otherProvider = User::factory()->create(['role' => 'insurer']);
    InsuranceProvider::create([
        'user_id' => $otherProvider->id,
        'organization_name' => 'Bahari Health',
        'license_number' => 'HOSP-313',
        'provider_type' => 'hospital',
        'status' => 'approved',
    ]);
    $unauthorizedEdit = $this->actingAs($otherProvider)->patch(route('provider.services.update', $service), [
        'name' => 'Hijacked service',
        'description' => 'Unauthorized',
        'price' => 1,
        'is_active' => '1',
    ]);

    $unauthorizedEdit->assertNotFound();
    $this->assertDatabaseHas('provider_services', [
        'id' => $service->id,
        'name' => 'General consultation',
        'price' => 5000,
    ]);
});

test('provider catalog escapes untrusted service descriptions', function () {
    $providerUser = User::factory()->create(['role' => 'insurer']);
    $provider = InsuranceProvider::create([
        'user_id' => $providerUser->id,
        'organization_name' => 'Bahari Health',
        'license_number' => 'HOSP-314',
        'provider_type' => 'hospital',
        'status' => 'approved',
    ]);
    ProviderService::create([
        'insurance_provider_id' => $provider->id,
        'name' => 'Consultation',
        'description' => "<script>alert('xss')</script>",
        'price' => 5000,
        'is_active' => true,
    ]);
    $customer = User::factory()->create(['role' => 'individual']);

    $response = $this->actingAs($customer)->get(route('insurance.services.index'));

    $response->assertSee('&lt;script&gt;', false);
    $response->assertDontSee("<script>alert('xss')</script>", false);
});

test('loan applications are rejected when verified mobile transactions do not cover the premium', function () {
    $customer = User::factory()->create(['role' => 'individual']);
    $profile = InsuranceProfile::create([
        'user_id' => $customer->id,
        'full_name' => $customer->name,
        'phone' => '+255712345678',
        'sim_balance' => 250000,
        'coverage_goal' => 'family_protection',
        'monthly_premium' => 3500,
        'coverage_amount' => 700000,
    ]);
    MobileMoneyTransaction::create([
        'user_id' => $customer->id,
        'reference' => 'insufficient-credit-305',
        'amount' => 3000,
        'transaction_type' => 'credit',
        'occurred_at' => now(),
        'verification_status' => 'verified',
    ]);

    $response = $this->actingAs($customer)->from(route('insurance.quote'))->post(route('insurance.loans.store'), [
        'disbursement_destination' => 'insurer',
    ]);

    $response->assertRedirect(route('insurance.quote'));
    $response->assertSessionHasErrors('disbursement_destination');
    $this->assertDatabaseMissing('insurance_loan_applications', [
        'user_id' => $customer->id,
        'insurance_profile_id' => $profile->id,
    ]);
});

test('eligible customer can submit a loan application using verified mobile transactions', function () {
    $customer = User::factory()->create(['role' => 'individual']);
    $profile = InsuranceProfile::create([
        'user_id' => $customer->id,
        'full_name' => $customer->name,
        'phone' => '+255712345678',
        'sim_balance' => 70000,
        'coverage_goal' => 'family_protection',
        'monthly_premium' => 3500,
        'coverage_amount' => 700000,
    ]);
    MobileMoneyTransaction::create([
        'user_id' => $customer->id,
        'reference' => 'verified-credit-loan-506',
        'amount' => 70000,
        'transaction_type' => 'credit',
        'occurred_at' => now(),
        'verification_status' => 'verified',
    ]);

    $response = $this->actingAs($customer)->post(route('insurance.loans.store'), [
        'disbursement_destination' => 'insurer',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('insurance_loan_applications', [
        'user_id' => $customer->id,
        'insurance_profile_id' => $profile->id,
        'requested_amount' => 50400,
        'status' => 'submitted',
    ]);
});

test('admin cannot approve a loan until the mobile-money balance is verified', function () {
    $administrator = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'individual']);
    $providerUser = User::factory()->create(['role' => 'insurer']);
    $provider = InsuranceProvider::create([
        'user_id' => $providerUser->id,
        'organization_name' => 'Bahari Insurance',
        'license_number' => 'TZ-INS-305',
        'provider_type' => 'insurer',
        'status' => 'approved',
    ]);
    $profile = InsuranceProfile::create([
        'user_id' => $customer->id,
        'full_name' => $customer->name,
        'phone' => '+255712345678',
        'sim_balance' => 250000,
        'coverage_goal' => 'family_protection',
        'monthly_premium' => 3500,
        'coverage_amount' => 700000,
    ]);
    $application = InsuranceLoanApplication::create([
        'user_id' => $customer->id,
        'insurance_profile_id' => $profile->id,
        'reference' => 'loan-reference-305',
        'requested_amount' => 10000,
        'sim_balance_at_application' => 250000,
        'disbursement_destination' => 'insurer',
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($administrator)->patch(route('admin.loans.review', $application), [
        'status' => 'approved',
        'insurance_provider_id' => $provider->id,
        'repayment_months' => 3,
        'monthly_repayment' => 3500,
    ]);

    $response->assertSessionHasErrors('status');
    $this->assertDatabaseHas('insurance_loan_applications', [
        'id' => $application->id,
        'status' => 'submitted',
        'reviewed_by' => null,
    ]);
});

test('admin requires a provider transaction reference before marking a payment paid', function () {
    $administrator = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'individual']);
    $payment = InsurancePayment::create([
        'user_id' => $customer->id,
        'reference' => 'payment-reference-305',
        'phone' => '+255712345678',
        'amount' => 3500,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($administrator)->patch(route('admin.payments.status', $payment), [
        'status' => 'paid',
    ]);

    $response->assertSessionHasErrors('provider_reference');
    $this->assertDatabaseHas('insurance_payments', ['id' => $payment->id, 'status' => 'pending']);
});

test('administrator confirms a payment with a receipt and customer sees it as paid', function () {
    $administrator = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'individual']);
    $payment = InsurancePayment::create([
        'user_id' => $customer->id,
        'reference' => 'payment-reference-306',
        'phone' => '+255712345678',
        'amount' => 3500,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($administrator)->patch(route('admin.payments.status', $payment), [
        'status' => 'paid',
        'provider_reference' => 'AIRTEL-RECEIPT-306',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('insurance_payments', [
        'id' => $payment->id,
        'status' => 'paid',
        'provider_reference' => 'AIRTEL-RECEIPT-306',
        'confirmation_source' => 'admin',
        'confirmed_by' => $administrator->id,
    ]);
    $this->assertDatabaseHas('audit_events', [
        'actor_id' => $administrator->id,
        'event' => 'insurance_payment.confirmed',
        'auditable_id' => $payment->id,
    ]);
    expect($payment->fresh()->paid_at)->not->toBeNull();

    $dashboard = $this->actingAs($customer)->get(route('dashboard'));

    $dashboard->assertOk();
    $dashboard->assertSee('AIRTEL-RECEIPT-306');
    $dashboard->assertSee(__('Paid'));

    $adminPayments = $this->actingAs($administrator)->get(route('admin.payments', ['status' => 'paid']));

    $adminPayments->assertOk();
    $adminPayments->assertSee('AIRTEL-RECEIPT-306');
    $adminPayments->assertSee(__('Admin'));

    $duplicateConfirmation = $this->actingAs($administrator)->patch(route('admin.payments.status', $payment), [
        'status' => 'paid',
        'provider_reference' => 'DIFFERENT-RECEIPT-306',
    ]);

    $duplicateConfirmation->assertStatus(409);
    $this->assertDatabaseHas('insurance_payments', [
        'id' => $payment->id,
        'provider_reference' => 'AIRTEL-RECEIPT-306',
    ]);
});

test('non administrators cannot confirm a customer payment', function () {
    $customer = User::factory()->create(['role' => 'individual']);
    $payment = InsurancePayment::create([
        'user_id' => $customer->id,
        'reference' => 'payment-reference-307',
        'phone' => '+255712345678',
        'amount' => 3500,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($customer)->patch(route('admin.payments.status', $payment), [
        'status' => 'paid',
        'provider_reference' => 'UNAUTHORIZED-307',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('insurance_payments', [
        'id' => $payment->id,
        'status' => 'pending',
        'provider_reference' => null,
    ]);
});

test('administrator can mark an unsuccessful payment failed without claiming success', function () {
    $administrator = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'individual']);
    $payment = InsurancePayment::create([
        'user_id' => $customer->id,
        'reference' => 'payment-reference-309',
        'phone' => '+255712345678',
        'amount' => 3500,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($administrator)->patch(route('admin.payments.status', $payment), [
        'status' => 'failed',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('insurance_payments', [
        'id' => $payment->id,
        'status' => 'failed',
        'provider_reference' => null,
        'confirmation_source' => null,
        'confirmed_by' => null,
        'paid_at' => null,
    ]);
});

test('approving a verified insurer-directed loan creates a pending premium payment', function () {
    $administrator = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'individual']);
    $providerUser = User::factory()->create(['role' => 'insurer']);
    $provider = InsuranceProvider::create([
        'user_id' => $providerUser->id,
        'organization_name' => 'Bahari Insurance',
        'license_number' => 'TZ-INS-308',
        'provider_type' => 'insurer',
        'status' => 'approved',
    ]);
    $profile = InsuranceProfile::create([
        'user_id' => $customer->id,
        'full_name' => $customer->name,
        'phone' => '+255712345678',
        'sim_balance' => 250000,
        'coverage_goal' => 'family_protection',
        'monthly_premium' => 3500,
        'coverage_amount' => 700000,
    ]);
    $application = InsuranceLoanApplication::create([
        'user_id' => $customer->id,
        'insurance_profile_id' => $profile->id,
        'reference' => 'loan-reference-308',
        'requested_amount' => 50400,
        'sim_balance_at_application' => 250000,
        'disbursement_destination' => 'insurer',
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($administrator)->patch(route('admin.loans.review', $application), [
        'status' => 'approved',
        'insurance_provider_id' => $provider->id,
        'repayment_months' => 3,
        'monthly_repayment' => 1200,
        'verify_balance' => '1',
        'balance_verification_reference' => 'MOBILE-MONEY-VERIFICATION-308',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('insurance_loan_applications', [
        'id' => $application->id,
        'status' => 'approved',
    ]);
    expect($application->fresh()->balance_verified_at_application)->not->toBeNull();
    $this->assertDatabaseHas('audit_events', [
        'actor_id' => $administrator->id,
        'event' => 'insurance_profile.balance_verified',
        'auditable_id' => $profile->id,
    ]);
    $this->assertDatabaseHas('insurance_payments', [
        'user_id' => $customer->id,
        'insurance_loan_application_id' => $application->id,
        'amount' => 50400,
        'status' => 'pending',
        'phone' => '+255712345678',
    ]);
    $this->assertDatabaseHas('audit_events', [
        'actor_id' => $administrator->id,
        'event' => 'insurance_payment.requested_for_loan',
    ]);
});
