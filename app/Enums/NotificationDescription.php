<?php

namespace App\Enums;

use App\Enums\Language;
use App\Enums\NotificationType;

/**
 * Enum cho description của các loại thông báo.
 */
enum NotificationDescription: int
{
    case PAYMENT_COMPLETE = 1;
    case BOOKING_CONFIRMED = 2;
    case BOOKING_CANCELLED = 3;
    case BOOKING_REMINDER = 4;
    case WALLET_DEPOSIT = 5;
    case WALLET_WITHDRAW = 6;
    case CHAT_MESSAGE = 7;
    case TECHNICIAN_WALLET_NOT_ENOUGH = 8;
    case STAFF_APPLY_SUCCESS = 9;
    case STAFF_APPLY_REJECTED = 10;

    /**
     * Convert từ NotificationType sang NotificationDescription
     */
    public static function fromNotificationType(NotificationType $type): self
    {
        return self::from($type->value);
    }

    /**
     * Lấy description đa ngôn ngữ
     */
    public function getDesc(): array
    {
        return match ($this) {
            self::PAYMENT_COMPLETE => [
                'vi' => 'Thanh toán thành công',
                'en' => 'Payment completed',
                'cn' => '支付成功',
            ],
            self::BOOKING_CONFIRMED => [
                'vi' => 'Đặt lịch đã được xác nhận',
                'en' => 'Booking confirmed',
                'cn' => '预约已确认',
            ],
            self::BOOKING_CANCELLED => [
                'vi' => 'Đặt lịch đã bị hủy',
                'en' => 'Booking cancelled',
                'cn' => '预约已取消',
            ],
            self::BOOKING_REMINDER => [
                'vi' => 'Nhắc nhở: Bạn có lịch hẹn sắp tới',
                'en' => 'Reminder: You have an upcoming appointment',
                'cn' => '提醒：您有即将到来的预约',
            ],
            self::WALLET_DEPOSIT => [
                'vi' => 'Nạp tiền vào ví thành công',
                'en' => 'Wallet deposit successful',
                'cn' => '钱包充值成功',
            ],
            self::WALLET_WITHDRAW => [
                'vi' => 'Rút tiền từ ví thành công',
                'en' => 'Wallet withdrawal successful',
                'cn' => '钱包提现成功',
            ],
            self::CHAT_MESSAGE => [
                'vi' => 'Bạn có tin nhắn mới',
                'en' => 'You have a new message',
                'cn' => '您有新消息',
            ],
            self::TECHNICIAN_WALLET_NOT_ENOUGH => [
                'vi' => 'Ví của bạn không đủ số dư để nhận lịch hẹn',
                'en' => 'Your wallet is not enough to receive the appointment',
                'cn' => '您的钱包余额不足，无法接收预约',
            ]
            ,
            self::STAFF_APPLY_SUCCESS => [
                'vi' => 'Đơn ứng tuyển của bạn đã được chấp nhận bởi hệ thống, bạn có thể tiến hành thực hiện dịch vụ ',
                'en' => 'Your staff apply has been accepted by the system, you can now proceed with the service',
                'cn' => '员工申请成功',
            ],
            self::STAFF_APPLY_REJECTED => [
                'vi' => 'Đơn ứng tuyển của bạn đã bị từ chối bởi hệ thống',
                'en' => 'Your staff apply has been rejected by the system',
                'cn' => '员工申请被拒绝',
            ],
        };
    }

    /**
     * Lấy description theo ngôn ngữ cụ thể
     */
    public function getDescByLang(Language $lang): string
    {
        $descriptions = $this->getDesc();
        return $descriptions[$lang->value] ?? $descriptions['vi'];
    }

    /**
     * Lấy title đa ngôn ngữ
     */
    public function getTitle(): array
    {
        return match ($this) {
            self::PAYMENT_COMPLETE => [
                'vi' => 'Thanh toán thành công',
                'en' => 'Payment Completed',
                'cn' => '支付成功',
            ],
            self::BOOKING_CONFIRMED => [
                'vi' => 'Xác nhận đặt lịch',
                'en' => 'Booking Confirmed',
                'cn' => '预约确认',
            ],
            self::BOOKING_CANCELLED => [
                'vi' => 'Hủy đặt lịch',
                'en' => 'Booking Cancelled',
                'cn' => '预约取消',
            ],
            self::BOOKING_REMINDER => [
                'vi' => 'Nhắc nhở lịch hẹn',
                'en' => 'Appointment Reminder',
                'cn' => '预约提醒',
            ],
            self::WALLET_DEPOSIT => [
                'vi' => 'Nạp tiền thành công',
                'en' => 'Deposit Successful',
                'cn' => '充值成功',
            ],
            self::WALLET_WITHDRAW => [
                'vi' => 'Rút tiền thành công',
                'en' => 'Withdrawal Successful',
                'cn' => '提现成功',
            ],
            self::CHAT_MESSAGE => [
                'vi' => 'Tin nhắn mới',
                'en' => 'New message',
                'cn' => '新消息',
            ],
            self::TECHNICIAN_WALLET_NOT_ENOUGH => [
                'vi' => 'Không đủ số dư',
                'en' => 'Not enough balance',
                'cn' => '余额不足',
            ],
            self::STAFF_APPLY_SUCCESS => [
                'vi' => 'Đơn ứng tuyển của bạn đã được chấp nhận',
                'en' => 'Your staff apply has been accepted',
                'cn' => '员工申请成功',
            ],
            self::STAFF_APPLY_REJECTED => [
                'vi' => 'Đơn ứng tuyển của bạn đã bị từ chối',
                'en' => 'Your staff apply has been rejected',
                'cn' => '员工申请被拒绝',
            ],
        };
    }

    /**
     * Lấy title theo ngôn ngữ cụ thể
     */
    public function getTitleByLang(Language $lang): string
    {
        $titles = $this->getTitle();
        return $titles[$lang->value] ?? $titles['vi'];
    }
}

