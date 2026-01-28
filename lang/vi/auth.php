<?php


return [
    'success' => [
        'otp_register' => 'Mã OTP đã được gửi. Hãy nhập mã xác thực.',
        'verify_register' => 'Số điện thoại đã được xác thực.',
        'register' => 'Tài khoản đã được đăng ký.',
        'login' => 'Bạn đã đăng nhập thành công.',
        'user' => 'Thông tin người dùng.',
        'set_language' => 'Ngôn ngữ đã được cập nhật.',
        'update_success' => 'Cập nhật thông tin thành công.',
        'logout' => 'Bạn đã đăng xuất thành công.',
        'lock_account' => 'Tài khoản đã bị khóa.',
    ],
    'error' => [
        'phone_verified' => 'Số điện thoại này đã được đăng ký.',
        'blocked' => 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau :minutes phút.',
        'already_sent' => 'Mã OTP đã được gửi. Vui lòng kiểm tra lại.',
        'invalid_otp' => 'Mã OTP không hợp lệ.',
        'otp_expired' => 'Mã OTP đã hết hạn.',
        'attempts_left' => 'Bạn đã nhập quá nhiều lần. Vui lòng thử lại sau :minutes phút.',
        'resend_otp' => 'Bạn đã gửi lại quá nhiều lần. Vui lòng thử lại sau :minutes phút.',
        'not_sent' => 'Mã OTP không tồn tại.',
        'phone_invalid' => 'Số điện thoại không hợp lệ.',
        'invalid_token_register' => 'Token đăng ký không hợp lệ.',
        'disabled' => 'Tài khoản này đã bị khóa, vui lòng liên hệ với quản trị viên để được hỗ trợ.',
        'invalid_login' => 'Số điện thoại hoặc mật khẩu không đúng.',
        'language_invalid' => 'Ngôn ngữ không hợp lệ.',
        'unauthorized' => 'Bạn không có quyền thực hiện hành động này.',
        'validation_failed' => 'Lỗi xác thực.',
        'unauthenticated' => 'Bạn chưa đăng nhập.',
        'wrong_password' => 'Mật khẩu cũ không đúng.',
    ],

    'admin' => [
        'phone' => 'Số điện thoại.',
        'password' => 'Mật khẩu.',
        'remember' => 'Ghi nhớ đăng nhập'
    ],
    'validation' => [
        'name_required' => 'Tên không được để trống.',
        'name_min' => 'Tên phải có ít nhất 4 ký tự.',
        'name_max' => 'Tên không được vượt quá 255 ký tự.',
        'address_invalid' => 'Địa chỉ không hợp lệ.',
        'address_max' => 'Địa chỉ không được vượt quá 255 ký tự.',
        'introduce_invalid' => 'Giới thiệu không hợp lệ.',
        'password_required' => 'Mật khẩu không được để trống.',
        'password_min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
        'confirm_password_same' => 'Mật khẩu xác nhận không khớp.',
        'date_invalid' => 'Ngày không hợp lệ.',
        'date_before' => 'Ngày không được vượt quá ngày hiện tại.',
    ],
];
