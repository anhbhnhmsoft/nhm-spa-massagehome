<?php

namespace App\Enums;

/**
 * Enum cho các loại thông báo.
 */
enum NotificationType: int
{
    case PAYMENT_COMPLETE = 1; // Thanh toán thành công
    case BOOKING_CONFIRMED = 2; // Xác nhận đặt lịch
    case BOOKING_CANCELLED = 3; // Hủy đặt lịch
    case BOOKING_REMINDER = 4; // Nhắc nhở lịch hẹn
    case WALLET_DEPOSIT = 5; // Nạp tiền thành công
    case WALLET_WITHDRAW = 6; // Rút tiền thành công
    case CHAT_MESSAGE = 7; // Tin nhắn mới
    case TECHNICIAN_WALLET_NOT_ENOUGH = 8; // Thông báo không đủ tiền trong ví kỹ thuật viên
    case STAFF_APPLY_SUCCESS = 9; // Thông báo ứng tuyển thành công
    case STAFF_APPLY_REJECTED = 10; // Thông báo ứng tuyển bị từ chối
    case BOOKING_REFUNDED = 11; // Thông báo hoàn tiền
    case BOOKING_COMPLETED = 12; // Thông báo hoàn thành
    case BOOKING_SUCCESS = 13; // Thông báo đặt lịch thành công
    case NEW_BOOKING_REQUEST = 14; // Thông báo có yêu cầu đặt lịch mới
    case BOOKING_AUTO_FINISHED = 15; // Thông báo hoàn thành tự động
    case BOOKING_OVERTIME_WARNING = 16; // Thông báo vượt quá thời gian hẹn
    case BOOKING_START = 17; // Thông báo bắt đầu lịch hẹn
    case WALLET_TRANSACTION_CANCELLED = 18; // Thông báo hủy giao dịch ví
    case PAYMENT_SERVICE_FOR_TECHNICIAN = 19; // Thông báo thanh toán cho kỹ thuật viên
    case DEPOSIT_SUCCESS = 20; // Thông báo nạp tiền thành công
    case DEPOSIT_FAILED = 21; // Thông báo nạp tiền thất bại
    case NOTIFICATION_MARKETING = 22; // Thông báo marketing

    public function getTitle(Language $lang, array $data = []): string
    {
        if ($this === self::NOTIFICATION_MARKETING) {
            return $data["title_{$lang->value}"] ?? $data['title_vi'] ?? '';
        }
        return __("notification.type.{$this->value}.title", $data, $lang->value);
    }
    public function getBody(Language $lang, array $data = []): string
    {
        if ($this === self::NOTIFICATION_MARKETING) {
            return $data["description_{$lang->value}"] ?? $data['description_vi'] ?? '';
        }
        return __("notification.type.{$this->value}.body", $data, $lang->value);
    }
}
