<?php

namespace App\Enums;

enum WalletTransactionType: int
{
    case DEPOSIT = 1; // Nạp tiền
    case WITHDRAWAL = 2; // Rút tiền (Yêu cầu)
    case PAYMENT = 3; // Thanh toán (Booking)
    case AFFILIATE = 4; // Nhận hoa hồng
    case REFUND = 5; // Hoàn tiền
}
