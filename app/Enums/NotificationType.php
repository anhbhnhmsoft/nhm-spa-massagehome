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

    /**
     * Lấy title đa ngôn ngữ
     */
    protected function getTitle(): array
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
                'vi' => 'Tạo giao dịch nạp tiền thành công',
                'en' => 'Wallet Deposit Successful',
                'cn' => '充值成功',
            ],
            self::WALLET_WITHDRAW => [
                'vi' => 'Tạo giao dịch rút tiền thành công',
                'en' => 'Wallet Withdrawal Successful',
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

            self::BOOKING_REFUNDED => [
                'vi' => 'Đơn đặt lịch đã được hoàn tiền thành công ',
                'en' => 'Booking refunded successfully',
                'cn' => '预约已成功退款',
            ],
            self::BOOKING_COMPLETED => [
                'vi' => 'Dịch vụ đã hoàn thành',
                'en' => 'Booking completed',
                'cn' => '预约已完成',
            ],
            self::BOOKING_SUCCESS => [
                'vi' => 'Đặt lịch thành công',
                'en' => 'Booking success',
                'cn' => '预约成功',
            ],
            self::NEW_BOOKING_REQUEST => [
                'vi' => 'Có yêu cầu đặt lịch mới',
                'en' => 'New booking request',
                'cn' => '有新的预约请求',
            ],
            self::BOOKING_AUTO_FINISHED => [
                'vi' => 'Hệ thống đã tự động hoàn thành booking',
                'en' => 'System auto-finished booking',
                'cn' => '系统已自动完成预约',
            ],
            self::BOOKING_OVERTIME_WARNING => [
                'vi' => 'Cảnh báo quá thời gian',
                'en' => 'Overtime Warning',
                'cn' => '超时警告',
            ],
            self::BOOKING_START => [
                'vi' => 'Bắt đầu dịch vụ',
                'en' => 'Start service',
                'cn' => '开始服务',
            ],
            self::WALLET_TRANSACTION_CANCELLED => [
                'vi' => 'Giao dịch ví đã bị hủy',
                'en' => 'Wallet transaction cancelled',
                'cn' => '钱包交易已取消',
            ],
            self::PAYMENT_SERVICE_FOR_TECHNICIAN => [
                'vi' => 'Thanh toán cho kỹ thuật viên thành công',
                'en' => 'Payment for technician successful',
                'cn' => '为技术人员支付成功',
            ],
        };
    }

    /**
     * Lấy description đa ngôn ngữ
     */
    protected function getDesc(): array
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
            ],
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

            self::BOOKING_REFUNDED => [
                'vi' => 'Đơn đặt lịch đã được hoàn tiền',
                'en' => 'Booking refunded',
                'cn' => '预约已成功退款',
            ],
            self::BOOKING_COMPLETED => [
                'vi' => 'Dịch vụ đã hoàn thành',
                'en' => 'Booking completed',
                'cn' => '预约已完成',
            ],
            self::BOOKING_SUCCESS => [
                'vi' => 'Đặt lịch thành công',
                'en' => 'Booking success',
                'cn' => '预约成功',
            ],
            self::NEW_BOOKING_REQUEST => [
                'vi' => 'Có yêu cầu đặt lịch mới',
                'en' => 'New booking request',
                'cn' => '有新的预约请求',
            ],
            self::BOOKING_AUTO_FINISHED => [
                'vi' => 'Booking đã được tự động hoàn thành do quá thời gian',
                'en' => 'Booking has been auto-finished due to overtime',
                'cn' => '预约已因超时自动完成',
            ],
            self::BOOKING_OVERTIME_WARNING => [
                'vi' => 'Cảnh báo: Booking đang quá thời gian dự kiến',
                'en' => 'Warning: Booking is overtime',
                'cn' => '警告：预约已超时',
            ],
            self::BOOKING_START => [
                'vi' => 'Chúc bạn có 1 trải nghiệm tuyệt vời',
                'en' => 'Welcome to your appointment',
                'cn' => '祝您旅途愉快',
            ],
            self::WALLET_TRANSACTION_CANCELLED => [
                'vi' => 'Giao dịch ví đã bị hủy',
                'en' => 'Wallet transaction cancelled',
                'cn' => '钱包交易已取消',
            ],
            self::PAYMENT_SERVICE_FOR_TECHNICIAN => [
                'vi' => 'Khách hàng đã đặt lịch hẹn với kỹ thuật viên, và đã thanh toán thành công vào ví của bạn',
                'en' => 'Customer has booked an appointment with the technician and successfully paid into your wallet',
                'cn' => '客户已为技术人员支付成功，已将款项存入您的钱包',
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
     * Lấy title theo ngôn ngữ cụ thể
     */
    public function getTitleByLang(Language $lang): string
    {
        $titles = $this->getTitle();
        return $titles[$lang->value] ?? $titles['vi'];
    }

}
