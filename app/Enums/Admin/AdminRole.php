<?php

namespace App\Enums\Admin;

use App\Core\Helper\EnumHelper;

enum AdminRole: int
{
    use EnumHelper;
    case ADMIN = 1; // Quản trị viên

    case ACCOUNTANT = 2; // Quản lý tài chính

    case EMPLOYEE = 3; // Nhân viên

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => __('admin.admin_role.ADMIN'),
            self::ACCOUNTANT => __('admin.admin_role.ACCOUNTANT'),
            self::EMPLOYEE => __('admin.admin_role.EMPLOYEE'),
        };
    }
}
