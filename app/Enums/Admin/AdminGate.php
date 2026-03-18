<?php

namespace App\Enums\Admin;

/**
 * Định nghĩa các quyền truy cập của admin
 */
enum AdminGate: string
{
    /**
     * Chỉ có admin mới có quyền truy cập
     */
    case ALLOW_ADMIN = 'allow_admin';
    /**
     * Chỉ có accountant mới có quyền truy cập (bao gồm cả admin)
     */
    case ALLOW_ACCOUNTANT = 'allow_accountant';

    /**
     * Chỉ có accountant mới có quyền truy cập (không bao gồm admin)
     */
    case ALLOW_ACCOUNTANT_SELF = 'allow_accountant_self';
    /**
     * Chỉ có employee mới có quyền truy cập (bao gồm cả admin)
     */
    case ALLOW_EMPLOYEE = 'allow_employee';

    /**
     * Chỉ có employee mới có quyền truy cập (không bao gồm admin)
     */
    case ALLOW_EMPLOYEE_SELF = 'allow_employee_self';

    /**
     * Full quyền truy cập
     */
    case ALLOW_FULL = 'allow_full';
}
