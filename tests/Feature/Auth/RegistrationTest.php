<?php

use App\Models\User;
use App\Notifications\VerifyEmailOtpNotification;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register and receive email verification otp', function () {
    Notification::fake();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'individual',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice', absolute: false));

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    Notification::assertSentTo($user, VerifyEmailOtpNotification::class);
    expect($user->otp)->not->toBeNull();
    expect($user->otp_expires_at)->not->toBeNull();
});

test('registration sends an otp the user can immediately verify with', function () {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'verify-me@example.com',
        'role' => 'individual',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'verify-me@example.com')->firstOrFail();

    $otp = null;

    Notification::assertSentTo($user, VerifyEmailOtpNotification::class, function ($notification) use (&$otp) {
        $otp = $notification->otp;

        return true;
    });

    expect($otp)->not->toBeNull();

    $response = $this->post('/email/verification-verify', ['otp' => $otp]);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false));
});
