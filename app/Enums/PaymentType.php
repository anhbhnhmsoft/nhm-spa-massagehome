<?php

namespace App\Enums;

enum PaymentType: int
{
    case QR_BANKING = 1; // Thanh toán qua mã QR
    case ZALO_PAY = 2; // Thanh toán qua Zalo Pay
    case MOMO_PAY = 3; // Thanh toán qua Momo Pay
}
