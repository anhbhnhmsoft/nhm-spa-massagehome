<?php

namespace App\Enums;

enum BookingStatus: int
{
    case PENDING = 1; // Chờ xác nhận
    case CONFIRMED = 2; // Đã xác nhận
    case ONGOING = 3; // Đang diễn ra
    case COMPLETED = 4; // Đã hoàn thành
    case CANCELED = 5; // Đã hủy

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('admin.booking.status.pending'),
            self::CONFIRMED => __('admin.booking.status.confirmed'),
            self::ONGOING => __('admin.booking.status.ongoing'),
            self::COMPLETED => __('admin.booking.status.completed'),
            self::CANCELED => __('admin.booking.status.canceled'),
        };
    }

    public static function toOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    public static function getLabel(int $value): string
    {
        return self::tryFrom($value)?->label() ?? '';
    }
}
