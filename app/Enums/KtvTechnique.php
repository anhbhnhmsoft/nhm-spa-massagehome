<?php

namespace App\Enums;

/**
 * Enum quản lý Kỹ thuật chuyên môn KTV
 */
enum KtvTechnique: int
{
    case ACUPRESSURE = 1;   // Ấn huyệt
    case MASSAGE = 2;       // Xoa bóp
    case THERAPY = 3;       // Trị liệu chuyên sâu
    case STRETCHING = 4;    // Giãn cơ
    case ESSENTIAL_OIL = 5;  // Thư giãn tinh dầu

    public function label(): string
    {
        return match ($this) {
            self::ACUPRESSURE => __('admin.ktv_technique.acupressure'),
            self::MASSAGE => __('admin.ktv_technique.massage'),
            self::THERAPY => __('admin.ktv_technique.therapy'),
            self::STRETCHING => __('admin.ktv_technique.stretching'),
            self::ESSENTIAL_OIL => __('admin.ktv_technique.essential_oil'),
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
