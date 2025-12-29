<?php

namespace App\Core\Cache;

enum CacheKey: string
{
    /**
     * Lưu mã OTP đăng ký.
     */
    case CACHE_KEY_OTP_REGISTER = 'CACHE_KEY_OTP_REGISTER';

    /**
     * Lưu số lần nhập sai OTP đăng ký.
     */
    case CACHE_KEY_OTP_REGISTER_ATTEMPTS = 'CACHE_KEY_OTP_REGISTER_ATTEMPTS';

    /**
     * Lưu số lần gửi lại OTP đăng ký.
     */
    case CACHE_KEY_RESEND_REGISTER_OTP = 'CACHE_KEY_RESEND_REGISTER_OTP';

    /**
     * Lưu token đăng ký tài khoản.
     */
    case CACHE_KEY_REGISTER_TOKEN = 'CACHE_KEY_REGISTER_TOKEN';

    /**
     * Lưu cấu hình hệ thống.
     */
    case CACHE_KEY_CONFIG = 'CACHE_KEY_CONFIG';

    /**
     * Lưu thông tin online của người dùng.
     */
    case CACHE_USER_HEARTBEAT = 'CACHE_USER_HEARTBEAT';

    /**
     * Lưu thông tin file của người dùng.
     */
    case CACHE_USER_FILE = 'CACHE_USER_FILE';


    /**
     * Lưu thông tin vị trí của người dùng.
     */
    case CACHE_USER_LOCATION = 'CACHE_USER_LOCATION';

    /**
     * Cache lưu thông tin Coupon.
     */
    case CACHE_COUPON = 'CACHE_COUPON';

    /**
     * Cache lưu số lần sử dụng của Coupon.
     */
    case CACHE_COUPON_USED = 'CACHE_COUPON_USED';

    /**
     * Lưu cấu hình Affiliate.
     */
    case CACHE_KEY_CONFIG_AFFILIATE = 'CACHE_KEY_CONFIG_AFFILIATE';
}
