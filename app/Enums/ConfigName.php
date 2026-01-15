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
    case SP_ZALO = 'SP_ZALO'; // trang Zalo hỗ trợ của admin
    case SP_FACEBOOK = 'SP_FACEBOOK'; // Trang Facebook hỗ trợ của admin
    case SP_PHONE = 'SP_PHONE'; // Số điện thoại hỗ trợ
    case SP_WECHAT = 'SP_WECHAT'; // link Wechat hỗ trợ của admin
    case DISCOUNT_RATE = 'DISCOUNT_RATE'; // Tỷ lệ chiết khấu dành cho kỹ thuật viên %
    case DISCOUNT_RATE_REFERRER_AGENCY = 'DISCOUNT_RATE_REFERRER_AGENCY'; // Tỷ lệ chiết khấu dành cho đại lý đối với 1 đơn hoàn thành của 1 KTV mà mình giới thiệu %
    case DISCOUNT_RATE_REFERRER_KTV = 'DISCOUNT_RATE_REFERRER_KTV'; // Tỷ lệ chiết khấu dành cho kỹ thuật viên đối với 1 đơn hoàn thành của 1 KTV mà mình giới thiệu %
    case DISCOUNT_RATE_REFERRER_KTV_LEADER = 'DISCOUNT_RATE_REFERRER_KTV_LEADER'; // Tỷ lệ chiết khấu dành cho kỹ thuật viên trưởng đối với 1 đơn hoàn thành của 1 KTV mà mình giới thiệu %
    case ZALO_MERCHANT_ID  = 'ZALO_MERCHANT_ID';
    case ZALO_MERCHANT_KEY_1  = 'ZALO_MERCHANT_KEY_1';
    case ZALO_MERCHANT_KEY_2  = 'ZALO_MERCHANT_KEY_2';
    case ZALO_APP_ID  = 'ZALO_APP_ID';
    case ZALO_APPSECRET_KEY  = 'ZALO_APPSECRET_KEY';
    case ZALO_OA_ID = 'ZALO_OA_ID';
    case ZALO_TEMPLATE_ID = 'ZALO_TEMPLATE_ID';
    case KTV_LEADER_MIN_REFERRALS = 'KTV_LEADER_MIN_REFERRALS'; // Số lượng KTV tối thiểu cần giới thiệu để trở thành trưởng nhóm KTV

}
