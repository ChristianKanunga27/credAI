<?php

namespace App\Exceptions;

use RuntimeException;

class ClickPesaPaymentException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?string $providerMessage = null,
    ) {
        parent::__construct($message);
    }

    public function shouldFallbackToControlNumber(): bool
    {
        $message = strtolower($this->providerMessage ?? '');

        return str_contains($message, 'payment method is not active')
            || str_contains($message, 'no payment collection methods');
    }

    public function customerMessage(): string
    {
        $message = strtolower($this->providerMessage ?? '');

        if (str_contains($message, 'payment method is not active')) {
            return __('ClickPesa USSD is not enabled for this mobile-money network. Choose Control Number, or enable this network in your ClickPesa collection settings.');
        }

        if (str_contains($message, 'no payment collection methods')) {
            return __('ClickPesa has no collection method enabled for this account. Enable USSD or BillPay in ClickPesa settings.');
        }

        if (str_contains($message, 'invalid / unsupported phone number') || str_contains($message, 'invalid phone number')) {
            return __('ClickPesa rejected this phone number. Check that it is a Tanzanian mobile-money number, then try again.');
        }

        if (str_contains($message, 'insufficient funds') || str_contains($message, 'not enough funds')) {
            return __('ClickPesa rejected the USSD request because the mobile-money account for this phone number has insufficient funds. Add the payment amount and any provider fees, then try again. No USSD prompt was sent.');
        }

        return __('ClickPesa could not start this payment. Please try the other mobile-money method or contact support.');
    }
}
