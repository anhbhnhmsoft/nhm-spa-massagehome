<?php

namespace App\Enums;

final class QueueKey
{
    const string LOCATIONS = 'locations';
    const string TRANSACTIONS_PAYMENT = 'transactions-payment';
    const string NOTIFICATIONS = 'notifications';
    const string REFUND_BOOKING_CANCEL = 'refund-booking-cancel';

    const string PAY_COMMISSION_FEE = 'pay-commission-fee';
}

