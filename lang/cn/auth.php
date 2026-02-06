<?php


return [
    'success' => [
        'otp_register' => 'OTP 验证码已发送。请输入验证码。',
        'verify_register' => '手机号码已通过验证。',
        'register' => '账号已注册。',
        'login' => '登录成功。',
        'user' => '用户信息。',
        'set_language' => '语言已更新。',
        'update_success' => '信息更新成功。',
        'logout' => '您已成功注销。',
        'lock_account' => '账号已被锁定。',
    ],
    'error' => [
        'phone_verified' => '此手机号码已验证。',
        'blocked' => '错误尝试过多。请在 :minutes 分钟后重试。',
        'already_sent' => 'OTP 验证码已发送。请检查。',
        'invalid_otp' => '无效的 OTP 验证码。',
        'otp_expired' => 'OTP 验证码已过期。',
        'attempts_left' => '尝试次数过多。请在 :minutes 分钟后重试。',
        'resend_otp' => '重发次数过多。请在 :minutes 分钟后重试。',
        'not_sent' => 'OTP 验证码不存在。',
        'phone_invalid' => '无效的手机号码。',
        'invalid_token_register' => '无效的注册令牌。',
        'disabled' => '此账号已被锁定，请联系管理员以获得支持。',
        'invalid_login' => '手机号码或密码不正确。',
        'language_invalid' => '无效的语言。',
        'unauthorized' => '您没有权限执行此操作。',
        'validation_failed' => '验证错误。',
        'unauthenticated' => '您尚未登录。',
        'wrong_password' => '旧密码不正确。',
    ],

    'admin' => [
        'phone' => '手机号码。',
        'password' => '密码。',
        'remember' => '记住我',
    ],
    'validation' => [
        'name_required' => '姓名不能为空。',
        'name_min' => '姓名至少包含 4 个字符。',
        'name_max' => '姓名不能超过 255 个字符。',
        'address_invalid' => '无效的地址。',
        'address_max' => '地址不能超过 255 个字符。',
        'introduce_invalid' => '无效的介绍。',
        'password_required' => '密码不能为空。',
        'password_min' => '密码至少包含 8 个字符。',
        'confirm_password_same' => '确认密码不匹配。',
        'date_invalid' => '无效的日期。',
        'date_before' => '日期不能晚于当前日期。',
    ],
];
