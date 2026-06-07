<?php

namespace App\Enums;

enum BookingApplicationStatus: int
{
    case APPLIED = 1;
    case SELECTED = 2;
    case REJECTED = 3;
    case REMOVED = 4;
    case EXPIRED = 5;

    public function label(): string
    {
        return match ($this) {
            self::APPLIED => __('admin.booking.application_status.applied'),
            self::SELECTED => __('admin.booking.application_status.selected'),
            self::REJECTED => __('admin.booking.application_status.rejected'),
            self::REMOVED => __('admin.booking.application_status.removed'),
            self::EXPIRED => __('admin.booking.application_status.expired'),
        };
    }
}
