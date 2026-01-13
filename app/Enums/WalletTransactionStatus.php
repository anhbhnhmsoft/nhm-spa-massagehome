<?php


namespace App\Enums;

enum WalletTransactionStatus: int
{
    case PENDING = 1; // Chờ xử lý
    case COMPLETED = 2; // (Thành công)
    case FAILED = 3; // (Thất bại)
    case CANCELLED = 4; // (Hủy)
    case REFUNDED = 5; // (Trả lại)
}
