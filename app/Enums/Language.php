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

}
