<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class OtpVerificationController extends Controller
{
    /**
     * Display the OTP verification view.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Send a fresh code when the user has no usable OTP, so the verification
        // screen is never shown without a code they can actually use.
        $hasUsableOtp = $user->otp
            && $user->otp_expires_at
            && $user->otp_expires_at->isFuture();

        if (! $hasUsableOtp) {
            $sent = $user->generateAndSendOtp();

            if ($sent) {
                session()->flash('status', 'otp-sent');
            } else {
                session()->flash('otp_send_failed', true);
            }
        }

        return view('auth.verify-otp', ['user' => $user]);
    }

    /**
     * Send a new OTP notification.
     */
    public function sendOtp(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $sent = $request->user()->generateAndSendOtp();

        if ($sent) {
            return back()->with('status', 'otp-sent');
        }

        return back()->withErrors([
            'email' => __('Failed to send OTP email. Please try again.'),
        ]);
    }

    /**
     * Verify the OTP and mark email as verified.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->merge([
            'otp' => str_replace(' ', '', trim((string) $request->input('otp'))),
        ]);

        $request->validate([
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        try {
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
                $user->forceFill([
                    'otp' => null,
                    'otp_expires_at' => null,
                ])->save();

                return redirect()->intended(route('dashboard', absolute: false))
                    ->with('status', 'verified');
            }
        } catch (\Throwable $e) {
            Log::error('OTP verification failed: '.$e->getMessage());
        }

        // If OTP is invalid or expired
        return back()->withErrors([
            'otp' => __('The provided OTP is invalid or has expired.'),
        ]);
    }
}
