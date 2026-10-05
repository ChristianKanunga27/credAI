<?php

namespace App\Enums;

enum InsurancePaymentConfirmationSource: string
{
    case Gateway = 'gateway';
    case Admin = 'admin';
}
