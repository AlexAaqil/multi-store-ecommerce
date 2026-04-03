<?php

namespace App\Enums;

enum UserRoles: int
{
    case SUPER_ADMIN = 0;
    case ADMIN = 1;
    case SELLER = 2;
    case CUSTOMER = 3;

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::SELLER => 'Seller',
            self::CUSTOMER => 'Customer',
        };
    }
}
