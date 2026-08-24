<?php

namespace App\Enums;

/**
 * Enum cho khung giờ dịch vụ quan tâm của khách hàng.
 * Dùng int backing type theo đúng quy chuẩn Enum MasaHome.
 */
enum PreferredTimeSlot: int
{
    case NIGHT = 1;
    case MORNING = 2;
    case AFTERNOON = 3;
    case EVENING = 4;

    public function label(): string
    {
        return match ($this) {
            self::NIGHT => __('admin.preferred_time_slot.night'),
            self::MORNING => __('admin.preferred_time_slot.morning'),
            self::AFTERNOON => __('admin.preferred_time_slot.afternoon'),
            self::EVENING => __('admin.preferred_time_slot.evening'),
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
