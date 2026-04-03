<?php

namespace App\Enums;

enum PaymentMethods: int
{
    case MPESA = 0;
    case CREDIT_CARD = 1;
    case PAYPAL = 2;
    case BANK_TRANSFER = 3;
    case COD = 4;
}
