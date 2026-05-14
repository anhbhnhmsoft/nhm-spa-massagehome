<?php

namespace App\Enums;

use App\Core\Helper\EnumHelper;

enum SupportMessageSenderType: string
{
    use EnumHelper;

    case CUSTOMER = 'customer';
    case STAFF = 'staff';
    case SYSTEM = 'system';

    public function dbValue(): int
    {
        return match ($this) {
            self::CUSTOMER => 0,
            self::STAFF => 1,
            self::SYSTEM => 2,
        };
    }

    public static function fromDbValue(int|string|null $value): self
    {
        return match ((int) $value) {
            0 => self::CUSTOMER,
            1 => self::STAFF,
            2 => self::SYSTEM,
            default => self::SYSTEM,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::CUSTOMER => 'Customer',
            self::STAFF => 'Staff',
            self::SYSTEM => 'System',
        };
    }
}
