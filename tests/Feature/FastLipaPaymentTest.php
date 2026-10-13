<?php

use App\Models\InsurancePayment;
use App\Models\InsuranceProfile;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.fastlipa.api_key', 'test-api-key');
    config()->set('services.fastlipa.api_url', 'https://api.fastlipa.com');
    config()->set('services.fastlipa.timeout', 15);
});

test('customer receives a FastLipa mobile-money prompt without transaction history', function () {
    Http::fake([
        '*api.fastlipa.com/v1/payments/mobile-money' => fn (Request $request) => Http::response([
            'id' => 'FASTLIPA-ID-123',
            'status' => 'PENDING',
            'reference' => $request['reference'],
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
    expect($payment->provider_reference)->toBe('FASTLIPA-ID-123');

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v1/payments/mobile-money')
        && $request->hasHeader('Authorization', 'Bearer test-api-key')
        && $request['reference'] === $payment->reference
        && $request['amount'] === '50400.00'
        && $request['currency'] === 'TZS'
        && $request['phone_number'] === '255712345678'
        && $request['callback_url'] === route('fastlipa.webhook'));
});

test('customer can create a mobile-money control number for the annual plan', function () {
    Http::fake([
        '*api.fastlipa.com/v1/payments/control-number' => fn (Request $request) => Http::response([
            'control_number' => $request['reference'],
            'status' => 'ACTIVE',
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
        ->and($payment->fastlipa_control_number)->toMatch('/^[0-9]{14}$/');
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/v1/payments/control-number')
        && $request['amount'] === 50400.0
        && $request['currency'] === 'TZS'
        && $request['customer_phone'] === '255712345678'
        && $request['callback_url'] === route('fastlipa.webhook'));
});

test('rejected FastLipa mobile-money prompt automatically falls back to a control number', function () {
    Http::fake([
        '*api.fastlipa.com/v1/payments/mobile-money' => Http::response([
            'message' => 'M-Pesa payment method is not active',
        ], 400),
        '*api.fastlipa.com/v1/payments/control-number' => fn (Request $request) => Http::response([
            'control_number' => $request['reference'],
            'status' => 'ACTIVE',
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

test('FastLipa webhook confirms a payment only after querying FastLipa', function () {
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
        '*api.fastlipa.com/v1/payments/ORDER123456ABC' => Http::response([
            'transaction_id' => 'FASTLIPA-PAYMENT-ID',
            'status' => 'SUCCESS',
            'reference' => 'ORDER123456ABC',
            'amount' => '50400.00',
            'currency' => 'TZS',
        ]),
    ]);

    $response = $this->postJson(route('fastlipa.webhook'), [
        'event' => 'payment.received',
        'data' => ['reference' => $payment->reference, 'status' => 'SUCCESS'],
    ]);

    $response->assertOk()->assertJson(['received' => true]);
    $this->assertDatabaseHas('insurance_payments', [
        'id' => $payment->id,
        'status' => 'paid',
        'provider_reference' => 'FASTLIPA-PAYMENT-ID',
        'confirmation_source' => 'gateway',
    ]);
    $this->assertDatabaseHas('insurance_policies', [
        'user_id' => $customer->id,
        'premium' => 50400,
        'coverage_amount' => 100000,
        'status' => 'active',
    ]);
    expect($payment->fresh()->policy)->not->toBeNull();
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v1/payments/ORDER123456ABC'));
});

test('FastLipa confirms a control-number payment after a verified webhook', function () {
    $customer = User::factory()->create(['role' => 'individual']);
    $payment = InsurancePayment::create([
        'user_id' => $customer->id,
        'collection_method' => 'control_number',
        'fastlipa_control_number' => '12345678901234',
        'reference' => 'ORDERCONTROL1234',
        'phone' => '+255712345678',
        'amount' => 50400,
        'currency' => 'TZS',
        'status' => 'pending',
    ]);

    Http::fake([
        '*api.fastlipa.com/v1/payments/12345678901234' => Http::response([
            'transaction_id' => 'FASTLIPA-CONTROL-ID',
            'status' => 'SUCCESS',
            'reference' => '12345678901234',
            'amount' => '50400.00',
            'currency' => 'TZS',
        ]),
    ]);

    $this->postJson(route('fastlipa.webhook'), [
        'event' => 'payment.received',
        'data' => ['reference' => '12345678901234', 'status' => 'SUCCESS'],
    ])->assertOk()->assertJson(['received' => true]);

    $this->assertDatabaseHas('insurance_payments', [
        'id' => $payment->id,
        'status' => 'paid',
        'provider_reference' => 'FASTLIPA-CONTROL-ID',
    ]);
    $this->assertDatabaseHas('insurance_policies', [
        'user_id' => $customer->id,
        'premium' => 50400,
        'coverage_amount' => 100000,
        'status' => 'active',
    ]);
});

test('FastLipa webhook does not confirm when the collected amount does not match', function () {
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
        '*api.fastlipa.com/v1/payments/ORDERDIFFERENT123' => Http::response([
            'transaction_id' => 'FASTLIPA-RECEIPT',
            'status' => 'SUCCESS',
            'reference' => 'ORDERDIFFERENT123',
            'amount' => '3000.00',
            'currency' => 'TZS',
        ]),
    ]);

    $this->postJson(route('fastlipa.webhook'), [
        'data' => ['reference' => $payment->reference],
    ])->assertOk();

    $this->assertDatabaseHas('insurance_payments', ['id' => $payment->id, 'status' => 'pending']);
});
