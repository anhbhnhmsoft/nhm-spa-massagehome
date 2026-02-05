<?php

namespace App\Enums;

enum NotificationAdminType
{
    case OVERDUE_ONGOING_BOOKING; // Kiểm tra booking quá hạn đang diễn ra
    case OVERDUE_CONFIRMED_BOOKING; // Kiểm tra booking quá hạn đã xác nhận
    case USER_APPLY_KTV_PARTNER; // Người dùng đăng ký làm đối tác KTV
    case USER_APPLY_AGENCY_PARTNER; // Người dùng đăng ký làm đối tác Agency
    case CONFIRM_WECHAT_PAYMENT; // Thanh toán qua wechat
    case EMERGENCY_SUPPORT; // Người dùng yêu cầu hỗ trợ khẩn cấp
}
