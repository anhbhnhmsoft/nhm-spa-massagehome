<?php
return [
    'heading' => 'Dashboard',
    'navigation_label' => 'Dashboard',
    'title' => 'Dashboard',
    'select_gap_heading' => 'Select the time range for aggregation',
    'select' => [
        'start_date_label' => 'Start date',
        'end_date_label' => 'End date',
        'start_date_required' => 'Start date cannot be blank',
        'start_date_max_date' => 'Start date must be before end date',
        'end_date_required' => 'End date cannot be blank',
        'end_date_min_date' => 'End date must be after start date',
    ],
    'general_stat' => [
        'total_booking' => 'Total bookings',
        'total_booking_desc' => 'Total bookings created ~ orders placed != orders created and completed',
        'completed_booking' => 'Total completed orders',
        'canceled_booking' => 'Total cancelled orders',
        'gross_revenue' => 'Net revenue',
        'gross_revenue_desc' => 'Revenue after deducting technician fees & commissions',
        'ktv_cost' => 'Technician costs',
        'ktv_cost_desc' => 'Total technician fees received from customers',
        'net_profit' => 'Profit',
        'net_profit_desc' => 'Profit received after operating expenses',
        'payment_failed' => 'Total failed payment orders',
        'payment_failed_desc' => 'Total failed payment orders ~ customer or technician balance is insufficient',
        'booking_confirmed' => 'Total confirmed orders',
        'booking_confirmed_desc' => 'Total confirmed orders ~ does not include completed or cancelled orders',
    ],
    'operation_cost' => [
        'active_order_count' => 'Orders in progress',
        'refund_amount' => 'Total amount refunded to the customer',
        'fee_amount' => 'Commission fee paid between customer and customer',
        'fee_amount_from_ktv_for_customer' => 'Commission fee paid from KTV to the customer',
        'deposit_amount' => 'Total amount deposited by the customer'
    ]
];
