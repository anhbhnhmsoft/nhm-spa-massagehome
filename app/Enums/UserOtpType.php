<?php

namespace App\Enums;


use App\Core\Helper\EnumHelper;

enum UserOtpType: int
{
    use EnumHelper;

    case REGISTER = 1;


}
