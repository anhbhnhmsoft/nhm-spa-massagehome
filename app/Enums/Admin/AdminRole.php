<?php

namespace App\Enums\Admin;

use App\Core\Helper\EnumHelper;

enum AdminRole: int
{
    use EnumHelper;
    case OPERATION_MANAGER = 1;
    case MARKETING_MANAGER = 2;
    case PROFILE_MANAGER = 3;
    case CUSTOMER_SUPPORT = 4;
    case SUPER_ADMIN = 5;

    public function label(): string
    {
        return match ($this) {
            self::OPERATION_MANAGER => __('admin.admin_role.OPERATION_MANAGER'),
            self::MARKETING_MANAGER => __('admin.admin_role.MARKETING_MANAGER'),
            self::PROFILE_MANAGER => __('admin.admin_role.PROFILE_MANAGER'),
            self::CUSTOMER_SUPPORT => __('admin.admin_role.CUSTOMER_SUPPORT'),
            self::SUPER_ADMIN => __('admin.admin_role.SUPER_ADMIN'),
        };
    }

    /**
     * Roles that can be assigned to regular management staff.
     * Superadmin must be granted explicitly by another superadmin.
     */
    public static function managementOptions(): array
    {
        return array_filter(
            self::toOptions(),
            fn (string $label, int $value): bool => $value !== self::SUPER_ADMIN->value,
            ARRAY_FILTER_USE_BOTH,
        );
    }
}
