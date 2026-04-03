<?php

namespace App\Enums;

enum PaymentStatuses: int
{
    case PENDING = 0;
    case PAID = 1;
    case CANCELLED = 2;
    case FAILED = 3;
    case REFUNDED = 4;
}
