<?php

return [
    'service' => [
        'not_active' => '服务已暂停',
        'not_found'  => '服务不存在',
        'not_found_profile' => '未找到技术员个人资料',
        'not_found_location' => '未找到技术员所在地'
    ],
    'not_found' => '预订不存在',
    'book_time_not_valid' => '预订时间无效，必须大于当前时间！',
    'ktv_is_busy_at_this_time' => '技师在此时间段已忙！',
    'status_not_confirmed' => '预订尚未确认',
    'already_started' => '预订已开始',
    'not_permission' => '您没有权限取消此预订',
    'booking_refunded' => '预订已退款',
    'not_canceled' => '预订尚未取消',
    'cancelled' => '预订已取消',
    'completed' => '预订已完成',


    'wallet' => [
        'not_active' => '账户未激活',
        'not_enough' => '余额不足',
        'tech_not_enough' => '技师余额不足',
        'tech_not_active' => '技师账户未激活'

    ],
    'service_option' => [
        'not_found'  => '服务选项不存在',
        'not_match'  => '服务选项不匹配',
    ],
    'discount_rate' => [
        'not_found' => '转换率不存在',
    ],
    'break_time_gap' => [
        'not_found' => '未找到休息时间间隔',
    ],
    'time_slot_not_available' => '技师在此时间段已安排服务，请选择其他时间',
    'coupon' => [
        'used' => '您已使用此优惠券',
        'not_found' => '优惠券不存在',
        'not_active' => '优惠券未激活',
        'not_yet_started' => '优惠券尚未开始',
        'expired' => '优惠券已过期',
        'not_match_service' => '优惠券与服务不匹配',
        'usage_limit_reached' => '优惠券使用限制已达上限',
        'max_discount_exceeded' => '优惠券超过最大折扣值',
        'used_successfully' => '优惠券已成功应用',
        'not_allowed_time' => '优惠券在此时间段不可用',
        'usage_limit_reached_or_daily_full' => '优惠券已达使用上限或今日已满',
    ],
    'payment' => [
        'wallet_customer' => '通过钱包支付预订',
        'wallet_technician' => '通过钱包支付给技师',
        'success' => '支付成功',
        'wallet_customer_not_enough' => '钱包余额不足',
        'wallet_technician_not_enough' => '技师钱包余额不足',
        'wallet_customer_not_found' => '未找到钱包账户',
        'wallet_technician_not_found' => '未找到技师钱包账户',
        'booking_not_found' => '未找到预订',
        'wallet_referred_staff' => '向推荐人支付佣金',
    ],
    'validate' => [
        'required' => '预订 ID 不能为空',
        'invalid' => '无效的预订 ID。',
        'integer' => '预订 ID 必须是整数。',
        'reason' => '取消原因必须是字符串。',
        'reason_required' => '取消原因不能为空',
    ],
    'cannot_cancel_ongoing_or_completed' => '无法取消正在进行或已完成的预订',
    'status_not_ongoing' => '预订非进行中状态',
    'pay_commission_fee_success' => '佣金计算成功',
    'started_successfully' => '服务启动成功',
    'refunded' => '预订已退款',
    'status_not_completed' => '预订非完成状态',
    'not_permission_at_this_time' => '您目前没有权限完成此预订',
    'booking_time_not_yet' => '预订开始时间尚未到达',
    'waiting_cancel' => '等待取消',
];
