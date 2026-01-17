<?php
return [
    'heading' => '儀表板',
    'navigation_label' => '儀表板',
    'title' => '儀表板',
    'select_gap_heading' => '选择聚合的时间范围',
    'select' => [
        'start_date_label' => '开始日期',
        'end_date_label' => '结束日期',
        'start_date_required' => '开始日期不能为空',
        'start_date_max_date' => '开始日期必须早于结束日期',
        'end_date_required' => '结束日期不能为空',
        'end_date_min_date' => '结束日期必须晚于开始日期',
    ],
    'general_stat' => [
        'total_booking' => '总预订量',
        'total_booking_desc' => '已创建预订量（已下单订单数不等于已创建并完成的订单数）',
        'completed_booking' => '已完成订单总数',
        'canceled_booking' => '已取消订单总数',
        'gross_revenue' => '净收入',
        'gross_revenue_desc' => '扣除技术人员费用和佣金后的收入',
        'ktv_cost' => '技术人员成本',
        'ktv_cost_desc' => '收取的技术人员费用总额',
        'net_profit' => '利润',
        'net_profit_desc' => '扣除运营费用后的利润'
    ],
    'operation_cost' => [
        'active_order_count' => '正在进行的订单数量',
        'refund_amount' => '已退款给客户的总金额',
        'fee_amount' => '已支付的佣金金额',
        'deposit_amount' => '客户已存入的总金额'

    ]
];
