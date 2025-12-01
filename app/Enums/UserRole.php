<?php

namespace App\Enums;

/**
 * Enum cho các vai trò người dùng.
 * Dùng Backed Enum (int) để map 1:1 với database.
 */
enum UserRole: int
{
    // Giá trị 0 thường được để trống cho các trường hợp đặc biệt (ví dụ: 'chưa gán')
    // Chúng ta sẽ bắt đầu từ 1.

    /**
     * Customer: Đặt dịch vụ massage (tại nhà hoặc tại spa), thanh toán, đánh giá.
     */
    case CUSTOMER = 1;

    /**
     * KTV: Nhận lịch, thực hiện dịch vụ, cập nhật trạng thái, Đăng ký dịch vụ
     */
    case KTV = 2;

    /**
     * Agency: (Spa) cung cấp KTV, quản lý KTV (là đối tác của hệ thống)
     */
    case AGENCY = 3;

    /**
     * Admin: Quản lý toàn bộ hệ thống
     */
    case ADMIN = 4;
}
