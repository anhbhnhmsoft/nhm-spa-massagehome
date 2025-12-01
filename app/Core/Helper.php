<?php

namespace App\Core;

use App\Enums\UserRole;
use Illuminate\Support\Str;

final class Helper
{

    /**
     * Tạo mã tham gia mới cho người dùng dựa trên vai trò.
     * @param UserRole $role
     * @return string
     */
    public static function generateReferCodeUser(UserRole $role): string
    {
        $fix = match ($role) {
            UserRole::ADMIN => 'ADM-',
            UserRole::AGENCY => 'AGN-',
            UserRole::KTV => 'KTV-',
            UserRole::CUSTOMER => 'CST-',
        };
        return $fix . self::generateReferCode();
    }

    /**
     * Tạo mã tham gia ngẫu nhiên 8 ký tự in hoa.
     * @return string
     */
    public static function generateReferCode(): string
    {
        return strtoupper(substr(Str::uuid()->toString(), 0, 8));
    }
    /**
     * Tạo token ngẫu nhiên 60 ký tự.
     * @return string
     */
    public static function generateTokenRandom(): string
    {
        return Str::random(60);
    }
}
