<?php

namespace App\Enums;

enum AddressTypes: int
{
    case SHIPPING = 0;
    case BILLING = 1;
    case BOTH = 2;
}
