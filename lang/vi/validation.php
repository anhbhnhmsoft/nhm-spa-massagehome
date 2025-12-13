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
    'location' => [
        'keyword_required' => 'Từ khóa không được để trống',
        'keyword_string' => 'Từ khóa phải là chuỗi',
        'latitude_numeric' => 'Vĩ độ phải là số',
        'longitude_numeric' => 'Kinh độ phải là số',
        'radius_numeric' => 'Khoảng cách phải là số',
        'limit_numeric' => 'Giới hạn phải là số',
        'place_id_required' => 'ID địa điểm không được để trống',
        'place_id_string' => 'ID địa điểm phải là chuỗi',
        'address_string' => 'Địa chỉ phải là chuỗi',
        'address_required' => 'Địa chỉ không được để trống',
        'latitude_required' => 'Vĩ độ không được để trống',
        'latitude_numeric' => 'Vĩ độ phải là số',
        'latitude_between' => 'Vĩ độ phải trong khoảng -90 đến 90',
        'longitude_required' => 'Kinh độ không được để trống',
        'longitude_numeric' => 'Kinh độ phải là số',
        'longitude_between' => 'Kinh độ phải trong khoảng -180 đến 180',
        'desc_string' => 'Mô tả phải là chuỗi',
        'is_primary_boolean' => 'is_primary phải là boolean',
    ],
];
