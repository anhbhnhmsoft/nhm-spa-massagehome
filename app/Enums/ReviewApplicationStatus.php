<?php

namespace App\Enums;

/**
 * Enum cho trạng thái đánh giá KTV, Agency
 */
enum ReviewApplicationStatus: int
{
    case PENDING = 1; // Chờ duyệt
    case APPROVED = 2; // Duyệt
    case REJECTED = 3; // Từ chối
}
