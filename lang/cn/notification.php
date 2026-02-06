<?php

use App\Enums\NotificationType;

return [
    'marked_as_read' => '忽略',
    'detail' => '详情',
    'overdue_ongoing_booking' => [
        'title' => '警告：正在进行的服务已超过结束时间',
        'body' => '服务 ID :booking_id 自 :start_time 开始，时长 :duration 分钟。',
    ],
    'overdue_confirmed_booking' => [
        'title' => '警告：已确认的服务已逾期，技师尚未开始',
        'body' => '服务 ID :booking_id 预约时间 :booking_time，时长 :duration 分钟。',
    ],
    'user_apply_ktv_partner' => [
        'title' => '技师合作伙伴注册申请',
        'body' => '用户 (ID: :user_id) 刚刚注册成为技师合作伙伴。',
    ],
    'user_apply_agency_partner' => [
        'title' => '代理合作伙伴注册申请',
        'body' => '用户 (ID: :user_id) 刚刚注册成为代理合作伙伴。',
    ],
    'confirm_wechat_payment' => [
        'title' => '确认微信支付',
        'body' => '确认交易 ID :transaction_id 的微信支付。',
    ],
    'emergency_support' => [
        'title' => '紧急支持请求',
        'body' => '针对服务 ID :booking_id 的紧急支持请求。',
    ],
    'type' => [
        NotificationType::PAYMENT_COMPLETE->value => [
            'title' => '支付成功',
            'body'  => '您的支付交易已完成。',
        ],
        NotificationType::BOOKING_CONFIRMED->value => [
            'title' => '预约已确认',
            'body'  => '您的预约已成功确认。',
        ],
        NotificationType::BOOKING_CANCELLED->value => [
            'title' => '预约已取消',
            'body'  => '您的预约已被取消。',
        ],
        NotificationType::BOOKING_REMINDER->value => [
            'title' => '预约提醒',
            'body'  => '您有一个即将到来的预约。请检查时间。',
        ],
        NotificationType::WALLET_DEPOSIT->value => [
            'title' => '钱包充值',
            'body'  => '钱包充值请求已成功创建。',
        ],
        NotificationType::WALLET_WITHDRAW->value => [
            'title' => '钱包提现',
            'body'  => '您的钱包提现请求已获批准，请检查您的银行余额。',
        ],
        NotificationType::CHAT_MESSAGE->value => [
            'title' => '新消息',
            'body'  => '您收到了来自系统的新消息。',
        ],
        NotificationType::TECHNICIAN_WALLET_NOT_ENOUGH->value => [
            'title' => '余额不足',
            'body'  => '您的钱包余额不足以接受此新预约。',
        ],
        NotificationType::STAFF_APPLY_SUCCESS->value => [
            'title' => '申请成功',
            'body'  => '您的合作伙伴申请已被接受。您可以开始工作了。',
        ],
        NotificationType::STAFF_APPLY_REJECTED->value => [
            'title' => '申请被拒绝',
            'body'  => '抱歉，您的合作伙伴申请已被拒绝。',
        ],
        NotificationType::BOOKING_REFUNDED->value => [
            'title' => '退款成功',
            'body'  => '预约金额已退还至您的钱包。',
        ],
        NotificationType::BOOKING_COMPLETED->value => [
            'title' => '服务已完成',
            'body'  => '感谢您使用我们的服务。预约已完成。',
        ],
        NotificationType::BOOKING_SUCCESS->value => [
            'title' => '预约成功',
            'body'  => '您已成功预约。请等待确认。',
        ],
        NotificationType::NEW_BOOKING_REQUEST->value => [
            'title' => '新请求',
            'body'  => '您有一个新的预约请求需要立即处理。',
        ],
        NotificationType::BOOKING_AUTO_FINISHED->value => [
            'title' => '自动完成',
            'body'  => '由于超过时间限制，系统已自动结束预约。',
        ],
        NotificationType::BOOKING_OVERTIME_WARNING->value => [
            'title' => '超时警告',
            'body'  => '预约已超过预计时间。请检查。',
        ],
        NotificationType::BOOKING_START->value => [
            'title' => '服务开始',
            'body'  => '您的服务正在开始。祝您体验愉快！',
        ],
        NotificationType::WALLET_TRANSACTION_CANCELLED->value => [
            'title' => '交易已取消',
            'body'  => '您的钱包交易已被取消。',
        ],
        NotificationType::PAYMENT_SERVICE_FOR_TECHNICIAN->value => [
            'title' => '收到付款',
            'body'  => '您已收到客户针对最近预约的付款。',
        ],
        NotificationType::DEPOSIT_SUCCESS->value => [
            'title' => '充值成功',
            'body'  => '您已成功向账户充值 :amount VND。',
        ],
        NotificationType::DEPOSIT_FAILED->value => [
            'title' => '充值失败',
            'body'  => '金额为 :amount VND 的充值交易失败。请重试或联系支持。',
        ],
    ],
];
