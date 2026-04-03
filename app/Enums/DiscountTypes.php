<?php

namespace App\Enums;

enum DiscountTypes: int
{
    case PERCENTAGE = 0;
    case FIXED_AMOUNT = 1;
    case BULK = 2;
    case BOGO = 3; // Buy one get one free
    case VOLUME = 4;
}
