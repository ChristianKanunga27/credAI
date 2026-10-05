<?php

use App\Models\InsurancePayment;
use App\Models\InsuranceProfile;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.clickpesa.client_id', 'test-client');
    config()->set('services.clickpesa.api_key', 'test-api-key');
    config()->set('services.clickpesa.api_url', 'https://api.clickpesa.com');
    config()->set('services.clickpesa.timeout', 15);
});

test('customer receives a ClickPesa USSD prompt without transaction history', function () {
    Http::fake([
        '*api.clickpesa.com/third-parties/generate-token' => Http::response([
            'success' => true,
            'token' => 'Bearer test-token',
        ]),
        '*api.clickpesa.com/third-parties/payments/preview-ussd-push-request' => Http::response([
            'activeMethods' => [['name' => 'M-PESA', 'status' => 'AVAILABLE']],
        ]),
        '*api.clickpesa.com/third-parties/payments/initiate-ussd-push-request' => fn (Request $request) => Http::response([
            'id' => 'CLICKPESA-ID-123',
            'status' => 'PROCESSING',
            'orderReference' => $request['orderReference'],
        ]),
    ]);

    $customer = User::factory()->create([
        'role' => 'individual',
        'phone' => '+255712345678',
    ]);
    InsuranceProfile::create([
        'user_id' => $customer->id,
        'full_name' => $customer->name,
        'phone' => '+255712345678',
        'sim_balance' => 0,
        'coverage_goal' => 'family_protection',
        'monthly_premium' => 3500,
        'coverage_amount' => 700000,
    ]);

    $response = $this->actingAs($customer)->post(route('insurance.payments.store'), [
        'phone' => '+255 712 345 678',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertDatabaseCount('insurance_payments', 1);
    $payment = InsurancePayment::query()->firstOrFail();
    expect($payment->reference)->toMatch('/^[A-Z0-9]{20}$/');
    expect($payment->status)->toBe('processing');
    expect($payment->provider_reference)->toBe('CLICKPESA-ID-123');

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/third-parties/generate-token')
        && $request->hasHeader('client-id', 'test-client')
        && $request->hasHeader('api-key', 'test-api-key'));
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/third-parties/payments/initiate-ussd-push-request')
        && $request->hasHeader('Authorization', 'Bearer test-token')
        && $request['orderReference'] === $payment->reference
        && $request['amount'] === '50400.00'
        && $request['currency'] === 'TZS'
        && $request['phoneNumber'] === '255712345678');
});

test('customer can create a mobile-money control number for the annual plan', function () {
    Http::fake([
        '*api.clickpesa.com/third-parties/generate-token' => Http::response(['token' => 'Bearer test-token']),
        '*api.clickpesa.com/third-parties/billpay/create-customer-control-number' => fn (Request $request) => Http::response([
            'billPayNumber' => $request['billReference'],
        ]),
    ]);

    $customer = User::factory()->create(['role' => 'individual', 'phone' => '+255712345678']);
    InsuranceProfile::create([
        'user_id' => $customer->id,
        'full_name' => $customer->name,
        'phone' => $customer->phone,
        'monthly_premium' => 3500,
        'coverage_amount' => 700000,
    ]);

    $response = $this->actingAs($customer)->post(route('insurance.payments.store'), [
        'phone' => $customer->phone,
        'collection_method' => 'control_number',
    ]);

    $response->assertRedirect(route('dashboard'));
    $payment = InsurancePayment::query()->firstOrFail();
    expect($payment->collection_method)->toBe('control_number')
        ->and($payment->status)->toBe('pending')
        ->and($payment->amount)->toBe('50400.00')
        ->and($payment->clickpesa_control_number)->toMatch('/^[0-9]{14}$/');
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/third-parties/billpay/create-customer-control-number')
        && $request['billAmount'] === 50400
        && $request['billPaymentMode'] === 'EXACT'
        && $request['customerPhone'] === '255712345678');
});

test('inactive ClickPesa USSD network automatically falls back to a control number', function () {
    Http::fake([
        '*api.clickpesa.com/third-parties/generate-token' => Http::response(['token' => 'Bearer test-token']),
        '*api.clickpesa.com/third-parties/payments/preview-ussd-push-request' => Http::response([
            'activeMethods' => [['name' => 'M-Pesa', 'status' => 'AVAILABLE']],
        ]),
        '*api.clickpesa.com/third-parties/payments/initiate-ussd-push-request' => Http::response([
            'message' => 'M-Pesa payment method is not active',
        ], 400),
        '*api.clickpesa.com/third-parties/billpay/create-customer-control-number' => fn (Request $request) => Http::response([
            'billPayNumber' => $request['billReference'],
        ]),
    ]);

    $customer = User::factory()->create(['role' => 'individual', 'phone' => '+255712345678']);
    InsuranceProfile::create([
        'user_id' => $customer->id,
        'full_name' => $customer->name,
        'phone' => $customer->phone,
        'monthly_premium' => 3500,
        'coverage_amount' => 700000,
    ]);

    $response = $this->actingAs($customer)->from(route('insurance.quote'))->post(route('insurance.payments.store'), [
        'phone' => $customer->phone,
        'collection_method' => 'ussd',
    ]);

    $response->assertRedirect(route('dashboard'))
        ->assertSessionHas('status');
    $this->assertDatabaseHas('insurance_payments', [
        'user_id' => $customer->id,
        'status' => 'pending',
        'collection_method' => 'control_number',
    ]);
});

test('customer can generate a direct-payment quote without syncing transaction history', function () {
    $customer = User::factory()->create(['role' => 'individual']);

    $response = $this->actingAs($customer)->postJson(route('insurance.quote.store'), [
        'name' => $customer->name,
        'phone' => '0712345678',
        'sim_balance' => 250000,
        'coverage_goal' => 'family_protection',
    ]);

    $response->assertOk()->assertJsonPath('status', 'quote_ready');
    $this->assertDatabaseHas('users', ['id' => $customer->id, 'phone' => '0712345678']);
    $this->assertDatabaseHas('insurance_profiles', [
        'user_id' => $customer->id,
        'phone' => '0712345678',
        'sim_balance' => 250000,
    ]);
});

test('ClickPesa webhook confirms a payment only after querying ClickPesa', function () {
    $customer = User::factory()->create(['role' => 'individual']);
    $payment = InsurancePayment::create([
        'user_id' => $customer->id,
        'reference' => 'ORDER123456ABC',
        'phone' => '+255712345678',
        'amount' => 50400,
        'currency' => 'TZS',
        'status' => 'pending',
    ]);

    Http::fake([
        '*api.clickpesa.com/third-parties/generate-token' => Http::response([
            'success' => true,
            'token' => 'Bearer test-token',
        ]),
        '*api.clickpesa.com/third-parties/payments/ORDER123456ABC' => Http::response([[
            'id' => 'CLICKPESA-PAYMENT-ID',
            'status' => 'SUCCESS',
            'paymentReference' => 'RECEIPT123',
            'orderReference' => 'ORDER123456ABC',
            'collectedAmount' => '50400.00',
            'collectedCurrency' => 'TZS',
        ]]),
    ]);

    $response = $this->postJson(route('clickpesa.webhook'), [
        'event' => 'PAYMENT RECEIVED',
        'data' => ['orderReference' => $payment->reference, 'status' => 'SUCCESS'],
    ]);

    $response->assertOk()->assertJson(['received' => true]);
    $this->assertDatabaseHas('insurance_payments', [
        'id' => $payment->id,
        'status' => 'paid',
        'provider_reference' => 'RECEIPT123',
        'confirmation_source' => 'gateway',
    ]);
    $this->assertDatabaseHas('insurance_policies', [
        'user_id' => $customer->id,
        'premium' => 50400,
        'coverage_amount' => 100000,
        'status' => 'active',
    ]);
    expect($payment->fresh()->policy)->not->toBeNull();
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/third-parties/payments/ORDER123456ABC'));
});

test('ClickPesa confirms a control-number payment after a verified BillPay webhook', function () {
    $customer = User::factory()->create(['role' => 'individual']);
    $payment = InsurancePayment::create([
        'user_id' => $customer->id,
        'collection_method' => 'control_number',
        'clickpesa_control_number' => '12345678901234',
        'reference' => 'ORDERCONTROL1234',
        'phone' => '+255712345678',
        'amount' => 50400,
        'currency' => 'TZS',
        'status' => 'pending',
    ]);

    Http::fake([
        '*api.clickpesa.com/third-parties/generate-token' => Http::response(['token' => 'Bearer test-token']),
        '*api.clickpesa.com/third-parties/payments/12345678901234' => Http::response([[
            'id' => 'CLICKPESA-BILLPAY-ID',
            'status' => 'SUCCESS',
            'paymentReference' => 'BILLPAY-RECEIPT-1234',
            'orderReference' => '12345678901234',
            'collectedAmount' => '50400.00',
            'collectedCurrency' => 'TZS',
        ]]),
    ]);

    $this->postJson(route('clickpesa.webhook'), [
        'event' => 'PAYMENT RECEIVED',
        'data' => ['orderReference' => '12345678901234', 'status' => 'SUCCESS'],
    ])->assertOk()->assertJson(['received' => true]);

    $this->assertDatabaseHas('insurance_payments', [
        'id' => $payment->id,
        'status' => 'paid',
        'provider_reference' => 'BILLPAY-RECEIPT-1234',
    ]);
    $this->assertDatabaseHas('insurance_policies', [
        'user_id' => $customer->id,
        'premium' => 50400,
        'coverage_amount' => 100000,
        'status' => 'active',
    ]);
});

test('ClickPesa webhook does not confirm when the collected amount does not match', function () {
    $customer = User::factory()->create(['role' => 'individual']);
    $payment = InsurancePayment::create([
        'user_id' => $customer->id,
        'reference' => 'ORDERDIFFERENT123',
        'phone' => '+255712345678',
        'amount' => 3500,
        'currency' => 'TZS',
        'status' => 'pending',
    ]);

    Http::fake([
        '*api.clickpesa.com/third-parties/generate-token' => Http::response(['token' => 'Bearer test-token']),
        '*api.clickpesa.com/third-parties/payments/ORDERDIFFERENT123' => Http::response([[
            'status' => 'SUCCESS',
            'paymentReference' => 'RECEIPT123',
            'orderReference' => 'ORDERDIFFERENT123',
            'collectedAmount' => '3000.00',
            'collectedCurrency' => 'TZS',
        ]]),
    ]);

    $this->postJson(route('clickpesa.webhook'), [
        'data' => ['orderReference' => $payment->reference],
    ])->assertOk();

    $this->assertDatabaseHas('insurance_payments', ['id' => $payment->id, 'status' => 'pending']);
});
