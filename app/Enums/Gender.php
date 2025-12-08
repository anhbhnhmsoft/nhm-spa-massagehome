<?php

namespace App\Enums;

/**
 * Enum cho giới tính.
 * Dùng Backed Enum (string) để map 1:1 với database.
 */
enum Gender: int
{
    case MALE = 1;
    case FEMALE = 2;

    public function label(): string
    {
        return match ($this) {
            self::MALE => __('admin.common.gender.male'),
            self::FEMALE => __('admin.common.gender.female'),
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
