<x-guest-layout>
    <div class="auth-title">
        <h2>{{ __('Verify your email') }}</h2>
        <p>{{ __('Enter the OTP sent to your email to verify your account.') }}</p>
    </div>
    
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('We have sent an OTP to ') }}<strong>{{ $user->email }}</strong>{{ __('. Please enter the 6-digit code below to verify your email address.') }}
    </div>

    @if (session('status') == 'otp-sent')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ __('A new OTP has been sent to the email address you provided.') }}
        </div>
    @endif

    @if (session('otp_send_failed'))
        <div class="mb-4 font-medium text-sm text-red-600 dark:text-red-400">
            {{ __('Failed to send OTP email. Please try again.') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-md">
            <div class="font-medium text-red-800 dark:text-red-200">{{ __('Please correct the following errors:') }}</div>
            <ul class="mt-2 list-disc pl-5 text-sm text-red-700 dark:text-red-300">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('verification.otp.verify') }}">
        @csrf

        <!-- OTP -->
        <div class="mt-4">
            <x-input-label for="otp" :value="__('OTP Code')" />
            <x-text-input id="otp" class="block mt-1 w-full text-center text-2xl font-mono tracking-widest font-semibold" type="text" name="otp" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required autofocus autocomplete="one-time-code" placeholder="000000" />
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Verify Email') }}
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-4">
        <form method="POST" action="{{ route('verification.otp.send') }}">
            @csrf
            <x-secondary-button type="submit">
                {{ __('Resend OTP') }}
            </x-secondary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>