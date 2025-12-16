<?php

namespace App\Enums;


enum DirectFile: string
{
    case KTVA = 'ktv';
    case AGENCY = 'agency';
    case SERVICE = 'service';
    case COUPON = 'coupon';
    case AVATAR_USER = 'avatar_user';

    public static function makePathById(DirectFile $type, string $id): string
    {
        return $type->value . "/" . $id;
    }
}
