<?php

namespace App\Enums;

enum AffiliateEarningStatus: int
{
    case PENDING = 1; // Chờ duyệt (ví dụ: chờ 30 ngày)
    case CLEARED = 2; // Đã duyệt (sẵn sàng chuyển vào ví)
    case CANCELLED = 3; // Bị hủy (ví dụ: đơn hàng bị refund)
}
