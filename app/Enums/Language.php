<?php

namespace App\Enums;

use App\Core\Helper\EnumHelper;

/**
 * Enum cho ngôn ngữ.
 * Dùng Backed Enum (string) để map 1:1 với database.
 */
enum Language: string
{
    use EnumHelper;
    case ENGLISH = 'en';
    case VIETNAMESE = 'vi';
    case CHINESE = 'cn';
    case JAPANESE = 'jp';
    case KOREAN = 'kr';

    public function label(): string
    {
        return match ($this) {
            self::ENGLISH => __('admin.language.english'),
            self::VIETNAMESE => __('admin.language.vietnamese'),
            self::CHINESE => __('admin.language.chinese'),
            self::JAPANESE => __('admin.language.japanese'),
            self::KOREAN => __('admin.language.korean'),
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

    public static function getLabel(?string $value): string
    {
        if (is_null($value)) {
            return '';
        }
        return self::tryFrom($value)?->label() ?? '';
    }
}
