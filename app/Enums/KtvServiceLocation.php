<?php

namespace App\Enums;

/**
 * Enum quản lý Địa điểm phục vụ của KTV
 */
enum KtvServiceLocation: int
{
    case HOME = 1;   // Phục vụ tại Nhà riêng
    case HOTEL = 2;  // Phục vụ tại Khách sạn

    public function label(): string
    {
        return match ($this) {
            self::HOME => __('admin.ktv_service_location.home'),
            self::HOTEL => __('admin.ktv_service_location.hotel'),
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

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
