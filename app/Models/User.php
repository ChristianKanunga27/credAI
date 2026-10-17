<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'avatar_url', 'otp', 'otp_expires_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function insuranceProfile()
    {
        return $this->hasOne(InsuranceProfile::class);
    }

    public function mobileMoneyTransactions(): HasMany
    {
        return $this->hasMany(MobileMoneyTransaction::class);
    }

    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    public function markEmailAsVerified(): bool
    {
        if ($this->hasVerifiedEmail()) {
            return false;
        }

        $this->forceFill(['email_verified_at' => $this->freshTimestamp()])->save();

        return true;
    }

    /**
     * Send the email verification notification containing the OTP.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->generateAndSendOtp();
    }

    /**
     * Generate a new 6-digit OTP, store its hash and expiration, and send notification.
     */
    public function generateAndSendOtp(): bool
    {
        try {
            $otp = (string) mt_rand(100000, 999999);

            $this->forceFill([
                'otp' => \Illuminate\Support\Facades\Hash::make($otp),
                'otp_expires_at' => now()->addMinutes(15),
            ])->save();

            $this->notify(new \App\Notifications\VerifyEmailOtpNotification($otp));

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to generate or send OTP verification email to '.$this->email.': '.$e->getMessage());

            return false;
        }
    }
}
