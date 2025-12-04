<?php

namespace App\Enums;

enum BookingStatus: int
{
    case PENDING = 1; // Chờ xác nhận
    case CONFIRMED = 2; // Đã xác nhận
    case ONGOING = 3; // Đang diễn ra
    case COMPLETED = 4; // Đã hoàn thành
    case CANCELED = 5; // Đã hủy
}
