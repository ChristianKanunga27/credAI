<?php

namespace App\Exceptions;

use RuntimeException;

class FastLipaPaymentException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?string $providerMessage = null,
    ) {
        parent::__construct($message);
    }

    public function customerMessage(): string
    {
        $message = strtolower($this->providerMessage ?? '');

        if (str_contains($message, 'payment method is not active') || str_contains($message, 'method not active')) {
            return __('FastLipa USSD is not enabled for this mobile-money network. Enable this network in your FastLipa collection settings, then try again.');
        }

        if (str_contains($message, 'no payment collection methods') || str_contains($message, 'no collection methods')) {
            return __('FastLipa has no collection method enabled for this account. Enable USSD in FastLipa settings.');
        }

        if (str_contains($message, 'invalid / unsupported phone number') || str_contains($message, 'invalid phone number')) {
            return __('FastLipa rejected this phone number. Check that it is a Tanzanian mobile-money number, then try again.');
        }

        if (str_contains($message, 'insufficient funds') || str_contains($message, 'not enough funds')) {
            return __('FastLipa rejected the USSD request because the mobile-money account for this phone number has insufficient funds. Add the payment amount and any provider fees, then try again. No USSD prompt was sent.');
        }

        return __('FastLipa could not send the USSD prompt. Check your phone number and try again, or contact support.');
    }
}