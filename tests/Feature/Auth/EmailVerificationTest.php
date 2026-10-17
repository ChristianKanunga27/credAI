<?php

use App\Models\User;
use App\Notifications\VerifyEmailOtpNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create([
        'otp' => Hash::make('123456'),
        'otp_expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('email verification screen automatically sends otp when user has no active otp', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create([
        'otp' => null,
        'otp_expires_at' => null,
    ]);

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
    Notification::assertSentTo($user, VerifyEmailOtpNotification::class);
    expect($user->fresh()->otp)->not->toBeNull();
    expect($user->fresh()->otp_expires_at)->not->toBeNull();
});

test('otp can be resent', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->post('/email/verification-send');

    Notification::assertSentTo($user, VerifyEmailOtpNotification::class);
    $response->assertSessionHas('status', 'otp-sent');
    $response->assertRedirect();
});

test('email can be verified with valid otp', function () {
    Event::fake();

    $user = User::factory()->unverified()->create([
        'otp' => Hash::make('654321'),
        'otp_expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->actingAs($user)->post('/email/verification-verify', [
        'otp' => '654321',
    ]);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    expect($user->fresh()->otp)->toBeNull();
    expect($user->fresh()->otp_expires_at)->toBeNull();
    $response->assertSessionHas('status', 'verified');
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('email is not verified with invalid otp', function () {
    $user = User::factory()->unverified()->create([
        'otp' => Hash::make('654321'),
        'otp_expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->actingAs($user)->post('/email/verification-verify', [
        'otp' => '111111',
    ]);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    $response->assertSessionHasErrors('otp');
});

test('email is not verified with expired otp', function () {
    $user = User::factory()->unverified()->create([
        'otp' => Hash::make('654321'),
        'otp_expires_at' => now()->subMinute(),
    ]);

    $response = $this->actingAs($user)->post('/email/verification-verify', [
        'otp' => '654321',
    ]);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    $response->assertSessionHasErrors('otp');
});

test('already verified user is redirected from email verification screen', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertRedirect(route('dashboard', absolute: false));
});

test('otp is cleared when the email cannot be sent', function () {
    $user = User::factory()->unverified()->create([
        'otp' => null,
        'otp_expires_at' => null,
    ]);

    mockMailerFailure();

    $sent = $user->generateAndSendOtp();

    expect($sent)->toBeFalse();
    expect($user->fresh()->otp)->toBeNull();
    expect($user->fresh()->otp_expires_at)->toBeNull();
});

test('verification screen reports a failed otp email', function () {
    $user = User::factory()->unverified()->create([
        'otp' => null,
        'otp_expires_at' => null,
    ]);

    mockMailerFailure();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
    $response->assertSessionHas('otp_send_failed', true);
});
