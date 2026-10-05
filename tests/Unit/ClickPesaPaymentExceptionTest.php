<?php

use App\Exceptions\ClickPesaPaymentException;
use Illuminate\Container\Container;

test('insufficient mobile-money funds explain why the USSD prompt was not sent', function () {
    $container = new Container;
    $container->instance('translator', new class
    {
        public function get(string $key, array $replace = [], ?string $locale = null, bool $fallback = true): string
        {
            return $key;
        }
    });
    Container::setInstance($container);

    $exception = new ClickPesaPaymentException(
        'ClickPesa could not send the mobile-money payment prompt.',
        400,
        'Insufficient funds in your Halopesa account. Please top up and try again.',
    );

    expect($exception->customerMessage())
        ->toContain('insufficient funds')
        ->toContain('No USSD prompt was sent.');
});
