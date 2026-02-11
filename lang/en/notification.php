<?php

use App\Enums\NotificationType;

return [
    'marked_as_read' => 'Mark as Read',
    'detail' => 'Detail',
    'overdue_ongoing_booking' => [
        'title' => 'Warning: Ongoing service has overdue end time',
        'body' => 'Service ID :booking_id started at :start_time, duration :duration minutes.',
    ],
    'overdue_confirmed_booking' => [
        'title' => 'Warning: Confirmed service is overdue, KTV has not started',
        'body' => 'Service ID :booking_id scheduled from :booking_time, duration :duration minutes.',
    ],
    'user_apply_ktv_partner' => [
        'title' => 'KTV Partner Registration Request',
        'body' => 'User (ID: :user_id) has just registered as a KTV partner.',
    ],
    'user_apply_agency_partner' => [
        'title' => 'Agency Partner Registration Request',
        'body' => 'User (ID: :user_id) has just registered as an agency partner.',
    ],
    'confirm_wechat_payment' => [
        'title' => 'Confirm WeChat Payment',
        'body' => 'Confirm WeChat payment for ID :transaction_id.',
    ],
    'emergency_support' => [
        'title' => 'Emergency Support Request',
        'body' => 'Emergency support request for service ID :booking_id.',
    ],
    'type' => [
        NotificationType::PAYMENT_COMPLETE->value => [
            'title' => 'Payment Successful',
            'body'  => 'Your payment transaction has been completed.',
        ],
        NotificationType::BOOKING_CONFIRMED->value => [
            'title' => 'Booking Confirmed',
            'body'  => 'Your appointment has been successfully confirmed.',
        ],
        NotificationType::BOOKING_CANCELLED->value => [
            'title' => 'Booking Cancelled',
            'body'  => 'Your appointment has been cancelled.',
        ],
        NotificationType::BOOKING_REMINDER->value => [
            'title' => 'Appointment Reminder',
            'body'  => 'You have an upcoming appointment. Please check the time.',
        ],
        NotificationType::WALLET_DEPOSIT->value => [
            'title' => 'Wallet Deposit',
            'body'  => 'Wallet deposit request has been created successfully.',
        ],
        NotificationType::WALLET_WITHDRAW->value => [
            'title' => 'Wallet Withdrawal',
            'body'  => 'Your wallet withdrawal request has been approved, please check your bank balance.',
        ],
        NotificationType::CHAT_MESSAGE->value => [
            'title' => 'New Message',
            'body'  => 'You received a new message from the system.',
        ],
        NotificationType::TECHNICIAN_WALLET_NOT_ENOUGH->value => [
            'title' => 'Insufficient Balance',
            'body'  => 'Your wallet does not have enough balance to accept this new appointment.',
        ],
        NotificationType::STAFF_APPLY_SUCCESS->value => [
            'title' => 'Application Successful',
            'body'  => 'Your partner application has been accepted. You can start working.',
        ],
        NotificationType::STAFF_APPLY_REJECTED->value => [
            'title' => 'Application Rejected',
            'body'  => 'Sorry, your partner application has been rejected.',
        ],
        NotificationType::BOOKING_REFUNDED->value => [
            'title' => 'Refund Successful',
            'body'  => 'The amount for the appointment has been refunded to your wallet.',
        ],
        NotificationType::BOOKING_COMPLETED->value => [
            'title' => 'Service Completed',
            'body'  => 'Thank you for using our service. The appointment is complete.',
        ],
        NotificationType::BOOKING_SUCCESS->value => [
            'title' => 'Booking Successful',
            'body'  => 'You have successfully booked an appointment. Please wait for confirmation.',
        ],
        NotificationType::NEW_BOOKING_REQUEST->value => [
            'title' => 'New Request',
            'body'  => 'You have a new booking request that needs immediate processing.',
        ],
        NotificationType::BOOKING_AUTO_FINISHED->value => [
            'title' => 'Auto Completed',
            'body'  => 'The system has automatically ended the appointment due to time limits.',
        ],
        NotificationType::BOOKING_OVERTIME_WARNING->value => [
            'title' => 'Overtime Warning',
            'body'  => 'The appointment is exceeding the expected time. Please check.',
        ],
        NotificationType::BOOKING_START->value => [
            'title' => 'Service Started',
            'body'  => 'Your service is starting. Have a great experience!',
        ],
        NotificationType::WALLET_TRANSACTION_CANCELLED->value => [
            'title' => 'Transaction Cancelled',
            'body'  => 'Your wallet transaction has been cancelled.',
        ],
        NotificationType::PAYMENT_SERVICE_FOR_TECHNICIAN->value => [
            'title' => 'Payment Received',
            'body'  => 'You have received payment from the customer for the recent appointment.',
        ],
        NotificationType::DEPOSIT_SUCCESS->value => [
            'title' => 'Deposit Successful',
            'body'  => 'You have successfully deposited :amount VND into your account.',
        ],
        NotificationType::DEPOSIT_FAILED->value => [
            'title' => 'Deposit Failed',
            'body'  => 'Deposit transaction of :amount VND has failed. Please try again or contact support.',
        ],
        NotificationType::NOTIFICATION_MARKETING->value => [
            'title' => ':title',
            'body'  => ':content',
        ],
        NotificationType::BOOKING_REASSIGNED->value => [
            'title' => 'Service Reassigned',
            'body'  => 'Service ID :booking_id has been reassigned to you.',
        ],
    ],
];
