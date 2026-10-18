<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('serves the verification screen as https behind a tls-terminating proxy', function () {
    // Production sits behind nginx terminating TLS. The forwarded proto header is
    // the only signal that the original request was HTTPS; without trusted proxies
    // Laravel builds http:// URLs and the session cookie is rejected, which shows
    // up as a 419 on the next POST instead of a working verification screen.
    $user = User::factory()->unverified()->create([
        'otp' => Hash::make('123456'),
        'otp_expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->actingAs($user)
        ->withHeaders(['X-Forwarded-Proto' => 'https'])
        ->get('/verify-email');

    $response->assertStatus(200);
    expect(request()->isSecure())->toBeTrue();
});

it('accepts a verification post when the request arrives via https proxy', function () {
    $user = User::factory()->unverified()->create([
        'otp' => Hash::make('654321'),
        'otp_expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->actingAs($user)
        ->withHeaders(['X-Forwarded-Proto' => 'https'])
        ->post('/email/verification-verify', ['otp' => '654321']);

    $response->assertRedirect(route('dashboard', absolute: false));
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});
