<?php

namespace App\Enums;

/**
 * Enum cho trạng thái nhu cầu dịch vụ của khách hàng.
 * Dùng Backed Enum (int) để map 1:1 với database theo quy chuẩn MasaHome.
 */
enum DemandStatus: int
{
    case NEED_NOW = 1;
    case EXPLORING = 2;
    case BOOKED = 3;
    case NO_NEED = 4;

    /**
     * Trả về nhãn đa ngôn ngữ (Localization)
     */
    public function label(): string
    {
        return match ($this) {
            self::NEED_NOW => __('admin.demand_status.need_now'),
            self::EXPLORING => __('admin.demand_status.exploring'),
            self::BOOKED => __('admin.demand_status.booked'),
            self::NO_NEED => __('admin.demand_status.no_need'),
        };
    }

    /**
     * Màu sắc hiển thị badge
     */
    public function color(): string
    {
        return match ($this) {
            self::NEED_NOW => 'danger',
            self::EXPLORING => 'info',
            self::BOOKED => 'success',
            self::NO_NEED => 'gray',
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
     * Trả về mảng chứa tất cả giá trị của Enum
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
