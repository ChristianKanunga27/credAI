<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\Auth\Events\Verified;

class OtpVerificationController extends Controller
{
    /**
     * Display the OTP verification view.
     */
    public function show(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended(route('dashboard', absolute: false))
                    : view('auth.verify-otp', ['user' => $request->user()]);
    }

    /**
     * Send a new OTP notification.
     */
    public function sendOtp(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Generate a 6-digit OTP
        $otp = mt_rand(100000, 999999);
        
        // Store OTP and expiration time (15 minutes from now)
        $request->user()->update([
            'otp' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(15),
        ]);

        try {
            // Send OTP via email
            Mail::raw(
                "Hello " . ($request->user()->name ?? 'there') . ",\n\n" .
                "Your OTP for email verification is: {$otp}\n\n" .
                "This OTP will expire in 15 minutes.\n\n" .
                "If you didn't request this, please ignore this email.\n\n" .
                "Thanks,\nThe CredAI Team",
                function ($message) use ($request) {
                    $message->to($request->user()->email)
                            ->subject('Email Verification OTP - CredAI');
                }
            );

            return back()->with('status', 'otp-sent');
        } catch (\Exception $e) {
            // Log the error (in production, you'd use proper logging)
            \Log::error('Failed to send OTP email: '.$e->getMessage());

            return back()->withErrors([
                'email' => 'Failed to send OTP email. Please try again.',
            ]);
        }
    }

    /**
     * Verify the OTP and mark email as verified.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
        ]);

        $user = $request->user();

        // Check if OTP matches and is not expired
        if ($user->otp && 
            $user->otp_expires_at && 
            $user->otp_expires_at->isFuture() && 
            Hash::check($request->otp, $user->otp)) {
            
            // Mark email as verified
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            // Clear OTP fields
            $user->update([
                'otp' => null,
                'otp_expires_at' => null,
            ]);

            return redirect()->intended(route('dashboard', absolute: false))
                            ->with('status', 'verified');
        }

        // If OTP is invalid or expired
        return back()->withErrors([
            'otp' => 'The provided OTP is invalid or has expired.',
        ]);
    }
}
