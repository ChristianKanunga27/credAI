<?php

use App\Models\InsuranceLoanApplication;
use App\Models\InsurancePayment;
use App\Models\InsuranceProfile;
use App\Models\InsuranceProvider;
use App\Models\User;

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
    $response->assertSee(__('Insurance administration'));
    $response->assertSee(__('No insurance providers yet.'));
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

test('customer can create a pending mobile-money payment request from their own quote', function () {
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

    $response = $this->actingAs($customer)->post(route('insurance.payments.store'), [
        'phone' => '+255712345678',
        'mobile_money_provider' => 'airtel_money',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('insurance_payments', [
        'user_id' => $customer->id,
        'insurance_profile_id' => $profile->id,
        'amount' => 3500,
        'status' => 'pending',
        'mobile_money_provider' => 'airtel_money',
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

    $response = $this->actingAs($customer)->post(route('insurance.loans.store'), [
        'requested_amount' => 10000,
        'disbursement_destination' => 'customer',
        'disbursement_phone' => '+255712345678',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('insurance_loan_applications', [
        'user_id' => $customer->id,
        'insurance_profile_id' => $profile->id,
        'requested_amount' => 10000,
        'disbursement_destination' => 'customer',
        'disbursement_phone' => '+255712345678',
        'status' => 'submitted',
        'balance_verified_at_application' => null,
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
