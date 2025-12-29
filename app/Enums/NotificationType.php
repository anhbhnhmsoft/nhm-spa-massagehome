<?php

namespace App\Enums;

/**
 * Enum cho các loại thông báo.
 */
enum NotificationType: int
{
    case PAYMENT_COMPLETE = 1;
    case BOOKING_CONFIRMED = 2;
    case BOOKING_CANCELLED = 3;
    case BOOKING_REMINDER = 4;
    case WALLET_DEPOSIT = 5;
    case WALLET_WITHDRAW = 6;
    case CHAT_MESSAGE = 7;
    case TECHNICIAN_WALLET_NOT_ENOUGH = 8;
    case STAFF_APPLY_SUCCESS = 9;
    case STAFF_APPLY_REJECTED = 10;
    case BOOKING_REFUNDED = 11;
    case BOOKING_COMPLETED = 12;
    case BOOKING_SUCCESS = 13;
    case NEW_BOOKING_REQUEST = 14;
    case BOOKING_AUTO_FINISHED = 15;
    case BOOKING_OVERTIME_WARNING = 16;
    case BOOKING_START = 17;
}
