<?php

namespace App\Enums;

enum UserWithdrawInfoType: int
{
    case BANK = 1;
    case MOMO = 2;
    case ZALO = 3;
}
