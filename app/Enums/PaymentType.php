<?php

namespace App\Enums;

enum PaymentType: int
{
    case CASH = 1; // Tiền mặt
    case QR_BANKING = 2; // Thanh toán qua mã QR
    case ZALO_PAY = 3; // Thanh toán qua Zalo Pay
    case MOMO_PAY = 4; // Thanh toán qua Momo Pay
}
