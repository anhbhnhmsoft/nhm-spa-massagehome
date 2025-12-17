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
}
