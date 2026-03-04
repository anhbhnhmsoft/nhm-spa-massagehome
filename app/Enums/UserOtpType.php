<?php

namespace App\Enums;


use App\Core\Helper\EnumHelper;

enum UserOtpType: int
{
    use EnumHelper;

    // Xác thực lại số điện thoại khi đăng ký
    case REGISTER = 1;

    // Xác thực lại số điện thoại khi quên mật khẩu
    case FORGOT_PASSWORD = 2;

}
