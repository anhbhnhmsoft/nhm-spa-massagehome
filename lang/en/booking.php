<?php

return [
    'service' => [
        'not_active' => 'Service is currently unavailable',
        'not_found'  => 'Service does not exist',
    ],
    'not_found' => 'Booking does not exist',
    'book_time_not_valid' => 'Booking time is invalid, must be greater than current time!',
    'ktv_is_busy_at_this_time' => 'Technician is busy at this time!',
    'status_not_confirmed' => 'Booking is not confirmed yet',
    'already_started' => 'Booking has started',
    'not_permission' => 'You do not have permission to cancel this booking',
    'booking_refunded' => 'Booking has been refunded',
    'not_canceled' => 'Booking has not been canceled',
    'cancelled' => 'Booking has been canceled',
    'completed' => 'Booking has been completed',


    'wallet' => [
        'not_active' => 'Account is not active',
        'not_enough' => 'Insufficient balance',
        'tech_not_enough' => 'Technician balance insufficient',
        'tech_not_active' => 'Technician account is not active'

    ],
    'service_option' => [
        'not_found'  => 'Service option does not exist',
        'not_match'  => 'Service option mismatch',
    ],
    'discount_rate' => [
        'not_found' => 'conversion rate does not exist',
    ],
    'break_time_gap' => [
        'not_found' => 'break time gap not found',
    ],
    'time_slot_not_available' => 'Technician has a scheduled service during this time, please choose another time',
    'coupon' => [
        'used' => 'You have already used this coupon',
        'not_found' => 'Coupon does not exist',
        'not_active' => 'Coupon is not active',
        'not_yet_started' => 'Coupon has not started yet',
        'expired' => 'Coupon has expired',
        'not_match_service' => 'Coupon does not match service',
        'usage_limit_reached' => 'Coupon usage limit reached',
        'max_discount_exceeded' => 'Coupon exceeded max discount value',
        'used_successfully' => 'Coupon applied successfully',
        'not_allowed_time' => 'Coupon is not applicable at this time',
        'usage_limit_reached_or_daily_full' => 'Coupon usage limit reached or daily limit full',
    ],
    'payment' => [
        'wallet_customer' => 'Payment for booking via wallet',
        'wallet_technician' => 'Payment to technician via wallet',
        'success' => 'Payment successful',
        'wallet_customer_not_enough' => 'Wallet balance insufficient',
        'wallet_technician_not_enough' => 'Technician wallet balance insufficient',
        'wallet_customer_not_found' => 'Wallet account not found',
        'wallet_technician_not_found' => 'Technician wallet account not found',
        'booking_not_found' => 'Booking not found',
        'wallet_referred_staff' => 'Commission payment for referrer',
    ],
    'validate' => [
        'required' => 'Booking ID cannot be empty',
        'invalid' => 'Invalid Booking ID.',
        'integer' => 'Booking ID must be an integer.',
        'reason' => 'Cancellation reason must be a string.',
        'reason_required' => 'Cancellation reason cannot be empty',
    ],
    'cannot_cancel_ongoing_or_completed' => 'Cannot cancel ongoing or completed booking',
    'status_not_ongoing' => 'Booking is not in ongoing status',
    'pay_commission_fee_success' => 'Commission calculation successful',
    'started_successfully' => 'Service started successfully',
    'refunded' => 'Booking has been refunded',
    'status_not_completed' => 'Booking is not in completed status',
    'not_permission_at_this_time' => 'You do not have permission to complete this booking at this time',
    'booking_time_not_yet' => 'Booking start time has not arrived yet',
];
