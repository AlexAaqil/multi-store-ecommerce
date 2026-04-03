<?php

namespace App\Enums;

enum VendorPayoutStatuses: int
{
    case PENDING = 0;
    case PROCESSING = 1;
    case PAID = 2;
    case FAILED = 3;
}
