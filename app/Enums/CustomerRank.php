<?php

namespace App\Enums;

/**
 * Enum cho phân hạng khách hàng CRM (Standard, Gold, VIP).
 * Dùng Backed Enum (int) để map 1:1 với database theo quy chuẩn MasaHome.
 */
enum CustomerRank: int
{
    case STANDARD = 1;
    case GOLD = 2;
    case VIP = 3;

    /**
     * Trả về nhãn đa ngôn ngữ (Localization)
     */
    public function label(): string
    {
        return match ($this) {
            self::STANDARD => __('admin.customer_rank.standard'),
            self::GOLD => __('admin.customer_rank.gold'),
            self::VIP => __('admin.customer_rank.vip'),
        };
    }

    /**
     * Màu sắc hiển thị badge
     */
    public function color(): string
    {
        return match ($this) {
            self::STANDARD => 'gray',
            self::GOLD => 'warning',
            self::VIP => 'danger',
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
