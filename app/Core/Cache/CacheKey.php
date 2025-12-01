<?php

namespace App\Core\Cache;

enum CacheKey: string
{
    /**
     * Lưu mã OTP đăng ký/đăng nhập.
     */
    case CACHE_KEY_OTP_AUTH = 'CACHE_KEY_OTP_AUTH';

    /**
     * Đếm số lần nhập sai OTP.
     */
    case CACHE_KEY_OTP_ATTEMPTS = 'CACHE_KEY_OTP_ATTEMPTS';

    /**
     * Chặn request khi nhập sai quá nhiều.
     */
    case CACHE_KEY_OTP_BLOCK = 'CACHE_KEY_OTP_BLOCK';

     /**
      * Lưu token đăng ký tài khoản.
      */
     case CACHE_KEY_REGISTER_TOKEN = 'CACHE_KEY_REGISTER_TOKEN';
}
