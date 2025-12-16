<?php

return [
    'password' => [
        'required' => 'Mật khẩu không hợp lệ.',
        'min' => 'Mật khẩu phải có ít nhất :min ký tự.',
        'regex' => 'Mật khẩu phải chứa ít nhất một chữ hoa, một chữ thường và một số.',
    ],
    'name' => [
        'required' => 'Tên không hợp lệ.',
        'string' => 'Tên phải là chuỗi ký tự.',
        'max' => 'Tên phải có ít nhất :max ký tự.',
    ],
    'referral_code' => [
        'string' => 'Mã giới thiệu phải là chuỗi ký tự.',
    ],
    'gender' => [
        'required' => 'Giới tính không hợp lệ.',
        'in' => 'Giới tính không hợp lệ.',
    ],
    'language' => [
        'required' => 'Ngôn ngữ không hợp lệ.',
        'in' => 'Ngôn ngữ không hợp lệ.',
    ],
    'service_id' => [
        'required' => 'Vui lòng chọn dịch vụ.',
        'numeric' => 'Dịch vụ không hợp lệ.',
        'exists' => 'Dịch vụ không tồn tại.',
    ],
    'book_time' => [
        'required' => 'Vui lòng chọn thời gian.',
        'date' => 'Thời gian không hợp lệ.',
        'after' => 'Thời gian phải sau thời điểm hiện tại 1 tiếng.',
    ],
    'coupon_id' => [
        'exists' => 'Mã giảm giá không tồn tại.',
    ],
    'address' => [
        'required' => 'Vui lòng nhập địa chỉ.',
    ],
    'lat' => [
        'required' => 'Vui lòng nhập tọa độ latitude.',
    ],
    'lng' => [
        'required' => 'Vui lòng nhập tọa độ longitude.',
    ],
    'duration' => [
        'required' => 'Vui lòng chọn thời gian.',
        'in' => 'Thời gian không hợp lệ.',
    ],
    'amount' => [
        'required' => 'Vui lòng nhập số tiền.',
        'numeric' => 'Số tiền phải là số.',
        'min' => 'Số tiền phải lớn hơn 0.',
        'max' => 'Số tiền phải nhỏ hơn 50.000.000.',
    ],
    'payment_type' => [
        'required' => 'Vui lòng chọn hình thức thanh toán.',
        'in' => 'Hình thức thanh toán không hợp lệ.',
    ],
    'transaction_id' => [
        'required' => 'Vui lòng nhập mã giao dịch.',
        'numeric' => 'Mã giao dịch phải là số.',
        'exists' => 'Mã giao dịch không tồn tại trong hệ thống.',
    ],
];
