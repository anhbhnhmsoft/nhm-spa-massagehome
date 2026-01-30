<?php

namespace App\Enums;

enum NotificationAdminType
{
    case OVERDUE_ONGOING_BOOKING; // Kiểm tra booking quá hạn đang diễn ra
    case OVERDUE_CONFIRMED_BOOKING; // Kiểm tra booking quá hạn đã xác nhận
}
