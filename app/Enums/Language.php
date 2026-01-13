<?php

namespace App\Enums;

/**
 * Enum cho ngôn ngữ.
 * Dùng Backed Enum (string) để map 1:1 với database.
 */
enum Language: string
{
    case ENGLISH = 'en';
    case VIETNAMESE = 'vi';
    case CHINESE = 'cn';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
