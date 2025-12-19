<?php

namespace App\Enums;

enum ConfigName: string
{
    case PAYOS_CLIENT_ID = 'PAYOS_CLIENT_ID';
    case PAYOS_API_KEY = 'PAYOS_API_KEY';
    case PAYOS_CHECKSUM_KEY = 'PAYOS_CHECKSUM_KEY';
    case CURRENCY_EXCHANGE_RATE = 'CURRENCY_EXCHANGE_RATE'; // Tỷ giá đổi tiền VNĐ -> Point
    case GOONG_API_KEY = 'GOONG_API_KEY';
    case BREAK_TIME_GAP = 'BREAK_TIME_GAP'; // Khoảng cách giữa 2 lần phục vụ của kỹ thuật viên tính bằng phút
    case DISCOUNT_RATE = 'DISCOUNT_RATE'; // Tỷ lệ chiết khấu dành cho kỹ thuật viên %
    case SP_ZALO = 'SP_ZALO'; // trang Zalo hỗ trợ của admin
    case SP_FACEBOOK = 'SP_FACEBOOK'; // Trang Facebook hỗ trợ của admin
    case SP_PHONE = 'SP_PHONE'; // Số điện thoại hỗ trợ
    case SP_WECHAT = 'SP_WECHAT'; // link Wechat hỗ trợ của admin
}
