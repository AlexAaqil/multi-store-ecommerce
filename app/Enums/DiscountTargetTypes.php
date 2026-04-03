<?php

namespace App\Enums;

enum DiscountTargetTypes: int
{
    case ALL_PRODUCTS = 0;
    case CATEGORIES = 1;
    case SPECIFIC_PRODUCTS = 2;
}
