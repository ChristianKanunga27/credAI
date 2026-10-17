<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('reproduces the production 500 when the otp email cannot be sent', function () {
    $user = User::factory()->unverified()->create([
        'otp' => Hash::make('123456'),
        'otp_expires_at' => now()->addMinutes(15),
    ]);

    // Simulate production: the OTP is expired so show() tries to resend,
    // and the mailer transport is unreachable.
    $user->forceFill(['otp_expires_at' => now()->subMinute()])->save();

    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => '127.0.0.1',
        'mail.mailers.smtp.port' => 2525,
        'mail.mailers.smtp.timeout' => 2,
    ]);

    $response = $this->actingAs($user)->get('/verify-email');

    dump('STATUS: '.$response->getStatusCode());
    $response->assertStatus(200);
});
