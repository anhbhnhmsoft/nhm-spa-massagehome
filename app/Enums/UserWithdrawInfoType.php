<?php

namespace App\Enums;

enum UserWithdrawInfoType: int
{
    // Hiện tại chỉ cho bank
    case BANK = 1;

    public static function getConfig(self $userWithdrawInfoType): array
    {
        return match ($userWithdrawInfoType) {
            self::BANK => [
                'bank_bin',
                'bank_name',
                'bank_account',
                'bank_holder',
            ],
        };
    }
}
