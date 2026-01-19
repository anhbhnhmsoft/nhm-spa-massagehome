<?php

namespace App\Enums;


enum DirectFile: string
{
    case KTVA = 'ktv';
    case AGENCY = 'agency';
    case SERVICE = 'service';
    case COUPON = 'coupon';
    case BANNER = 'banner';
    case AVATAR_USER = 'avatar_user';
    case USER_FILE_UPLOAD = 'user_file_upload';
    case CONFIG = 'config';



    public static function makePathById(DirectFile $type, string $id): string
    {
        return $type->value . "/" . $id;
    }
}
