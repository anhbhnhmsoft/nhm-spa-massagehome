<?php

namespace App\Enums;

use App\Enums\Admin\AdminRole;

enum NotificationAdminType
{
    case OVERDUE_ONGOING_BOOKING; // Kiểm tra booking quá hạn đang diễn ra
    case OVERDUE_CONFIRMED_BOOKING; // Kiểm tra booking quá hạn đã xác nhận
    case USER_APPLY_KTV_PARTNER; // Người dùng đăng ký làm đối tác KTV
    case USER_APPLY_AGENCY_PARTNER; // Người dùng đăng ký làm đối tác Agency
    case CONFIRM_WECHAT_PAYMENT; // Thanh toán qua wechat
    case CONFIRM_ALIPAY_PAYMENT; // Thanh toán qua alipay
    case EMERGENCY_SUPPORT; // Người dùng yêu cầu hỗ trợ khẩn cấp


    /**
     * Xác định mảng các Role được phép nhận thông báo này
     * @return array<AdminRole>
     */
    public function getTargetRoles(): array
    {
        return match ($this) {
            self::EMERGENCY_SUPPORT => [
                AdminRole::ADMIN,
                AdminRole::CUSTOMER_SUPPORT,
            ],
            self::OVERDUE_ONGOING_BOOKING,
            self::OVERDUE_CONFIRMED_BOOKING => [
                AdminRole::ADMIN,
            ],
            self::USER_APPLY_KTV_PARTNER,
            self::USER_APPLY_AGENCY_PARTNER => [
                AdminRole::ADMIN,
            ],
            self::CONFIRM_WECHAT_PAYMENT,
            self::CONFIRM_ALIPAY_PAYMENT => [
                AdminRole::ADMIN,
            ],
            default => [AdminRole::ADMIN],
        };
    }
}
