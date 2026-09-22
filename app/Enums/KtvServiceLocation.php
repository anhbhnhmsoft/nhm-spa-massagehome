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
     * Chuẩn hóa 1 giá trị địa điểm (số nguyên, slug home/hotel hoặc text tiếng Việt) về ID Enum
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
            '1', 'home', 'nhà riêng', 'nha rieng', 'nhà', 'nha' => self::HOME->value,
            '2', 'hotel', 'khách sạn', 'khach san' => self::HOTEL->value,
            default => is_numeric($value) ? (self::tryFrom((int) $value)?->value) : null,
        };
    }

    /**
     * Chuẩn hóa danh sách địa điểm phục vụ về mảng các ID số nguyên duy nhất [1, 2]
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
