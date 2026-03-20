<?php

use App\Enums\NotificationType;

return [
    'marked_as_read' => 'Bỏ qua',
    'detail' => 'Chi tiết',
    'overdue_ongoing_booking' => [
        'title' => 'Cảnh báo dịch vụ đang diễn ra đã quá hạn giờ kết thúc',
        'body' => 'Dịch vụ ID :booking_id bắt đầu từ :start_time, thời gian làm :duration phút.',
    ],
    'overdue_confirmed_booking' => [
        'title' => 'Cảnh báo dịch vụ đã xác nhận đã quá hạn, KTV chưa bắt đầu làm dịch vụ',
        'body' => 'Dịch vụ ID :booking_id đặt lịch từ :booking_time, thời gian làm :duration phút.',
    ],
    'user_apply_ktv_partner' => [
        'title' => 'Yêu cầu đăng ký làm đối tác KTV',
        'body' => 'Người dùng (ID: :user_id) vừa đăng ký làm đối tác KTV.',
    ],
    'user_apply_agency_partner' => [
        'title' => 'Yêu cầu đăng ký làm đối tác đại lý',
        'body' => 'Người dùng (ID: :user_id) vừa đăng ký làm đối tác đại lý.',
    ],
    'confirm_wechat_payment' => [
        'title' => 'Xác nhận thanh toán wechat',
        'body' => 'Xác nhận thanh toán wechat ID :transaction_id.',
    ],
    'confirm_alipay_payment' => [
        'title' => 'Xác nhận thanh toán alipay',
        'body' => 'Xác nhận thanh toán alipay ID :transaction_id.',
    ],
    'emergency_support' => [
        'title' => 'Yêu cầu hỗ trợ khẩn cấp',
        'body' => 'Yêu cầu hỗ trợ khẩn cấp cho dịch vụ ID :booking_id.',
    ],
    'type' => [
        NotificationType::BOOKING_CANCELLED->value => [
            'title' => 'Hủy đặt lịch',
            'body'  => 'Lịch hẹn của bạn đã bị hủy.',
        ],
        NotificationType::WALLET_DEPOSIT->value => [
            'title' => 'Nạp tiền vào ví',
            'body'  => 'Yêu cầu nạp tiền vào ví đã được tạo thành công.',
        ],
        NotificationType::WALLET_WITHDRAW->value => [
            'title' => 'Rút tiền từ ví',
            'body'  => 'Yêu cầu rút tiền từ ví của bạn đã được duyệt thành công, vui lòng kiểm tra số dư trong ngân hàng của bạn.',
        ],
        NotificationType::CHAT_MESSAGE->value => [
            'title' => 'Tin nhắn mới',
            'body'  => 'Bạn nhận được một tin nhắn mới từ hệ thống.',
        ],
        NotificationType::STAFF_APPLY_SUCCESS->value => [
            'title' => 'Ứng tuyển thành công',
            'body'  => 'Đơn ứng tuyển đối tác của bạn đã được chấp nhận. Bạn có thể bắt đầu làm việc.',
        ],
        NotificationType::STAFF_APPLY_REJECTED->value => [
            'title' => 'Ứng tuyển bị từ chối',
            'body'  => 'Rất tiếc, đơn ứng tuyển đối tác của bạn đã bị từ chối.',
        ],
        NotificationType::BOOKING_COMPLETED->value => [
            'title' => 'Dịch vụ hoàn thành',
            'body'  => 'Cảm ơn bạn đã sử dụng dịch vụ. Lịch hẹn đã hoàn tất.',
        ],
        NotificationType::BOOKING_SUCCESS->value => [
            'title' => 'Đặt lịch thành công',
            'body'  => 'Bạn đã đặt lịch hẹn thành công. Vui lòng chờ xác nhận.',
        ],
        NotificationType::NEW_BOOKING_REQUEST->value => [
            'title' => 'Yêu cầu mới',
            'body'  => 'Bạn có một yêu cầu đặt lịch mới cần xử lý ngay.',
        ],
        NotificationType::BOOKING_START->value => [
            'title' => 'Bắt đầu dịch vụ',
            'body'  => 'Dịch vụ của bạn đang bắt đầu. Chúc bạn có một trải nghiệm tuyệt vời!',
        ],
        NotificationType::WALLET_TRANSACTION_CANCELLED->value => [
            'title' => 'Hủy giao dịch',
            'body'  => 'Giao dịch trong ví của bạn đã bị hủy bỏ.',
        ],
        NotificationType::PAYMENT_SERVICE_FOR_TECHNICIAN->value => [
            'title' => 'Nhận thanh toán',
            'body'  => 'Bạn đã nhận được thanh toán từ khách hàng cho lịch hẹn vừa rồi.',
        ],
        // Ví dụ dùng biến động :amount
        NotificationType::DEPOSIT_SUCCESS->value => [
            'title' => 'Nạp tiền thành công',
            'body'  => 'Bạn đã nạp thành công :amount VND vào tài khoản.',
        ],
        NotificationType::NOTIFICATION_MARKETING->value => [
            'title' => ':title',
            'body'  => ':content',
        ],
        NotificationType::BOOKING_REASSIGNED->value => [
            'title' => 'Dịch vụ được chuyển giao',
            'body'  => 'Dịch vụ ID :booking_id đã được chuyển giao cho bạn.',
        ],
    ],
];

