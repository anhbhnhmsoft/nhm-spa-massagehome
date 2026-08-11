<?php

namespace App\Enums\Admin;

/**
 * Định nghĩa các quyền truy cập của admin
 */
enum AdminGate: string
{
    case ALLOW_SUPER_ADMIN = 'allow_super_admin';
    case ALLOW_OPERATION = 'allow_operation';
    case ALLOW_MARKETING = 'allow_marketing';
    case ALLOW_PROFILE = 'allow_profile';
    case ALLOW_CUSTOMER_SUPPORT = 'allow_customer_support';
    case ALLOW_ORDER_DASHBOARD = 'allow_order_dashboard';
}
