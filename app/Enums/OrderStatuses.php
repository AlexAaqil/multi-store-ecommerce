<?php

namespace App\Enums;

enum OrderStatuses: int
{
    case PENDING = 0;
    case PROCESSING = 1;
    case CONFIRMED = 2;
    case SHIPPED = 3;
    case DELIVERED = 4;
    case CANCELLED = 5;
    case REFUNDED = 6;

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::CONFIRMED => 'Confirmed',
            self::SHIPPED => 'Shipped',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::CONFIRMED => 'primary',
            self::SHIPPED => 'secondary',
            self::DELIVERED => 'success',
            self::CANCELLED, self::REFUNDED => 'danger',
        };
    }
}
