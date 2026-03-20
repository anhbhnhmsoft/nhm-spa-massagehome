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
            self::ENGLISH => 'English',
            self::VIETNAMESE => 'Vietnamese',
            self::CHINESE => 'Chinese',
            self::JAPANESE => 'Japanese',
            self::KOREAN => 'Korean',
        };
    }
}
