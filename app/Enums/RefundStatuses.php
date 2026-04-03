<?php

namespace App\Enums;

enum RefundStatuses: int
{
    case PENDING = 0;
    case PROCESSED = 1;
    case FAILED = 2;
}
