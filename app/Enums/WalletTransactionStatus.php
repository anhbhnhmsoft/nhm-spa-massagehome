<?php


namespace App\Enums;

enum WalletTransactionStatus: int
{
    case PENDING = 1;
    case COMPLETED = 2; // (Thành công)
    case FAILED = 3; // (Thất bại)
}
