<?php

namespace App\Enums;

enum ConfigName: string
{
    case PAYOS_CLIENT_ID = 'PAYOS_CLIENT_ID';
    case PAYOS_API_KEY = 'PAYOS_API_KEY';
    case PAYOS_CHECKSUM_KEY = 'PAYOS_CHECKSUM_KEY';
    case CURRENCY_EXCHANGE_RATE = 'CURRENCY_EXCHANGE_RATE'; // Tỷ giá đổi tiền VNĐ -> Point
}
