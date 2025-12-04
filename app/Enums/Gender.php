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
}
