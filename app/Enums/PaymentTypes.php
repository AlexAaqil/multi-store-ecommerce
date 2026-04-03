<?php

namespace App\Enums;

enum PaymentTypes: int
{
    case PAYMENT = 0;
    CASE REFUND = 1;
    CASE CHARGEBACK = 2;
}
