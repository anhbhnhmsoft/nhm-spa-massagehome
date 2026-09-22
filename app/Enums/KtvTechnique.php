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

    public static function getLabel(?int $value): string
    {
        if (is_null($value)) {
            return '';
        }
        return self::tryFrom($value)?->label() ?? '';
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Chuẩn hóa 1 giá trị kỹ thuật (số nguyên, slug tiếng Anh hoặc text tiếng Việt) về ID Enum
     */
    public static function normalize(mixed $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof self) {
            return $value->value;
        }

        $str = mb_strtolower(trim((string) $value));

        return match ($str) {
            '1', 'acupressure', 'ấn huyệt', 'an huyet' => self::ACUPRESSURE->value,
            '2', 'massage', 'xoa bóp', 'xoa bop' => self::MASSAGE->value,
            '3', 'therapy', 'trị liệu', 'tri lieu', 'trị liệu chuyên sâu', 'tri lieu chuyen sau' => self::THERAPY->value,
            '4', 'stretching', 'giãn cơ', 'gian co' => self::STRETCHING->value,
            '5', 'essential_oil', 'aroma_relax', 'thư giãn tinh dầu', 'thu gian tinh dau', 'tinh dầu', 'tinh dau' => self::ESSENTIAL_OIL->value,
            default => is_numeric($value) ? (self::tryFrom((int) $value)?->value) : null,
        };
    }

    /**
     * Chuẩn hóa danh sách kỹ thuật về mảng các ID số nguyên duy nhất [1, 2, ...]
     */
    public static function normalizeList(mixed $items): array
    {
        if (blank($items)) {
            return [];
        }

        if (is_string($items)) {
            $decoded = json_decode($items, true);
            $items = is_array($decoded) ? $decoded : [$items];
        }

        $normalized = [];
        foreach ((array) $items as $item) {
            $val = self::normalize($item);
            if ($val !== null) {
                $normalized[] = $val;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Lấy label an toàn từ bất kỳ giá trị nào (ID, slug, chuỗi legacy)
     */
    public static function labelFrom(mixed $value): string
    {
        $id = self::normalize($value);
        return $id !== null ? self::getLabel($id) : (string) $value;
    }
}
