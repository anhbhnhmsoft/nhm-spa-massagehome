<?php
return [
    'heading' => 'Bảng Điều Khiển',
    'navigation_label' => 'Bảng Điều Khiển',
    'title' => 'Bảng Điều Khiển',
    'select_gap_heading' => 'Chọn phạm vi thời gian để tổng hợp',
    'select' => [
        'start_date_label' => 'Ngày bắt đầu',
        'end_date_label' => 'Ngày kết thúc',
        'start_date_required' => 'Ngày bắt đầu không được bỏ trống',
        'start_date_max_date' => 'Ngày bắt đầu phải trước ngày kết thúc',
        'end_date_required' => 'Ngày kết thúc không được để trống',
        'end_date_min_date' => 'Ngày kết thúc phải sau ngày bắt đầu',
    ],
    'general_stat' => [
        'title' => 'Tổng quan về doanh thu nền tảng',
        'total_booking' => 'Tổng số booking',
        'total_booking_desc' => 'Tổng số booking đã được tạo ~ đơn đã được đặt != đơn được tạo và đã hoàn thành',
        'completed_booking' => 'Tổng số đơn hoàn thành',
        'canceled_booking' => 'Tổng số đơn hủy',
        'gross_revenue' => 'Doanh thu thuần',
        'gross_revenue_desc' => 'Doanh thu đã trừ phí cho kỹ thuật viên & phí hoa hồng',
        'ktv_cost' => 'Chi phí kỹ thuật viên',
        'ktv_cost_desc' => 'Tổng chi phí kỹ thuật viên nhận được từ khách hàng',
        'net_profit' => 'Lợi nhuận',
        'net_profit_desc' => 'Lợi nhuận nhận được sau chi phí vận hành',
        'payment_failed' => 'Tổng số đơn thanh toán thất bại',
        'payment_failed_desc' => 'Tổng số đơn thanh toán thất bại ~ do số dư ví khách hàng hoặc kỹ thuật viên không đủ tiền',
        'booking_confirmed' => 'Tổng số đơn đã xác nhận',
        'booking_confirmed_desc' => 'Tổng số đơn đã được xác nhận chờ thực hiện ~ không bao gồm các đơn đã hoàn thành hoặc hủy',
    ],
    'operation_cost' => [
        'active_order_count' => 'Đơn đang thực hiện',
        'refund_amount' => 'Tổng số tiền đã hoàn cho khách hàng',
        'fee_amount' => 'Phí hoa hồng giữa khách hàng vs khách hàng',
        'fee_amount_from_ktv_for_customer' => 'Phí hoa hồng từ KTV cho trả cho người giới thiệu',
        'deposit_amount' => 'Tổng số tiền khách hàng đã nạp'
    ]
];
