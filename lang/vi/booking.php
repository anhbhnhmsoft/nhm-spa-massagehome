<?php

return [
    'service' => [
        'not_active' => 'Dịch vụ dừng phục vụ',
        'not_found'  => 'Dịch vụ không tồn tại',
    ],
    'not_found' => 'Booking không tồn tại',
    'book_time_not_valid' => 'Thời gian đặt không hợp lệ, cần phải lớn hơn thời gian hiện tại!',
    'ktv_is_busy_at_this_time' => 'Kỹ thuật viên đã có lịch trong khung giờ này!',
    'status_not_confirmed' => 'Booking chưa được xác nhận',
    'already_started' => 'Booking đã bắt đầu',
    'wallet' => [
        'not_active' => 'Tài khoản không hoạt động',
        'not_enough' => 'Số dư không đủ',
        'tech_not_enough' => 'Số dư kĩ thuật viên không đủ',
        'tech_not_active' => 'Tài khoản kĩ thuật viên không hoạt động'

    ],
    'service_option' => [
        'not_found'  => 'Tùy chọn dịch vụ không tồn tại',
        'not_match'  => 'Tùy chọn dịch vụ không khớp',
    ],
    'discount_rate' => [
        'not_found' => 'tỉ lệ chuyển đổi không tồn tại',
    ],
    'break_time_gap' => [
        'not_found' => 'không tìm thấy khoảng thời gian nghỉ',
    ],
    'time_slot_not_available' => 'Kỹ thuật viên đã lên lịch phục vụ trong khoảng thời gian này, vui lòng chọn thời gian khác',
    'coupon' => [
        'used' => 'Bạn đã sử dụng mã giảm giá này',
        'not_found' => 'Mã giảm giá không tồn tại',
        'not_active' => 'Mã giảm giá không hoạt động',
        'not_yet_started' => 'Mã giảm giá chưa bắt đầu',
        'expired' => 'Mã giảm giá đã hết hạn',
        'not_match_service' => 'Mã giảm giá không khớp với dịch vụ',
        'usage_limit_reached' => 'Mã giảm giá đã đạt đến giới hạn sử dụng',
        'max_discount_exceeded' => 'Mã giảm giá vượt quá giá trị giảm tối đa',
        'used_successfully' => 'Mã giảm giá đã được áp dụng',
        'not_allowed_time' => 'Mã giảm giá không áp dụng trong thời gian này',
        'usage_limit_reached_or_daily_full' => 'Mã giảm giá đã đạt đến giới hạn sử dụng hoặc đã hết lượt sử dụng trong ngày',
    ],
    'payment' => [
        'wallet_customer' => 'Thanh toán đặt dịch vụ bằng ví',
        'wallet_technician' => 'Thanh toán trả tiền cho kĩ thuật viên qua ví',
        'success' => 'Thanh toán thành công',
        'wallet_customer_not_enough' => 'Số dư ví không đủ',
        'wallet_technician_not_enough' => 'Số dư ví kĩ thuật viên không đủ',
        'wallet_customer_not_found' => 'Không tìm thấy tài khoản ví',
        'wallet_technician_not_found' => 'Không tìm thấy tài khoản ví kĩ thuật viên',
        'booking_not_found' => 'Không tìm thấy booking',
    ],
    'validate' => [
        'required' => 'Booking ID không được để trống',
        'invalid' => 'Booking ID không hợp lệ.',
    ],
];
