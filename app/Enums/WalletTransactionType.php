<?php

namespace App\Enums;

enum WalletTransactionType: int
{
    case DEPOSIT_QR_CODE = 1; // Nạp tiền qua mã QR
    case DEPOSIT_ZALO_PAY = 2; // Nạp tiền qua Zalo Pay
    case DEPOSIT_MOMO_PAY = 3; // Nạp tiền qua Momo Pay
    case WITHDRAWAL = 4; // Rút tiền (Yêu cầu)
    case PAYMENT = 5; // Thanh toán (Booking)
    case AFFILIATE = 6; // Nhận hoa hồng
    case PAYMENT_FOR_KTV = 7; // Thanh toán cho KTV
    case REFUND = 8; // Hoàn tiền cho customer
    case RETRIEVE_PAYMENT_REFUND_KTV = 9; // thu hồi tiền thanh toán cho KTV khi hủy booking

    // Lấy danh sách trạng thái giao dịch nạp vào ví
    public static function statusIn()
    {
        return [
            self::DEPOSIT_QR_CODE->value,
            self::DEPOSIT_ZALO_PAY->value,
            self::DEPOSIT_MOMO_PAY->value,
            self::AFFILIATE->value,
            self::PAYMENT_FOR_KTV->value,
            self::REFUND->value,
        ];
    }

    // Lấy danh sách trạng thái giao dịch rút ra khỏi ví
    public static function statusOut()
    {
        return [
            self::WITHDRAWAL->value,
        ];
    }
}
