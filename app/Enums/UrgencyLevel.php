<?php

namespace App\Enums;

/**
 * Enum cho mức độ gấp/nhu cầu của Yêu cầu dịch vụ.
 * Dùng Backed Enum (int) map với database theo quy chuẩn MasaHome.
 */
enum UrgencyLevel: int
{
    case NEED_NOW = 1;
    case TODAY = 2;
    case SCHEDULED = 3;

    /**
     * Trả về nhãn đa ngôn ngữ (Localization)
     */
    public function label(): string
    {
        return match ($this) {
            self::NEED_NOW => __('admin.urgency_level.need_now'),
            self::TODAY => __('admin.urgency_level.today'),
            self::SCHEDULED => __('admin.urgency_level.scheduled'),
        };
    }

    /**
     * Chuyển đổi danh sách Enum thành mảng Select Options cho Filament Form
     */
    public static function toOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    /**
     * Lấy nhãn hiển thị an toàn từ giá trị thô (int)
     */
    public static function getLabel(?int $value): string
    {
        if (is_null($value)) {
            return '';
        }
        return self::tryFrom($value)?->label() ?? '';
    }

    /**
     * Trả về mảng tất cả giá trị Enum
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
