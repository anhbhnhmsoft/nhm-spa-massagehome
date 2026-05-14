<?php

namespace App\Enums;

use App\Core\Helper\EnumHelper;

enum SupportTicketStatus: string
{
    use EnumHelper;

    case PENDING = 'pending';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case CLOSED = 'closed';

    public function dbValue(): int
    {
        return match ($this) {
            self::PENDING => 0,
            self::ASSIGNED => 1,
            self::IN_PROGRESS => 2,
            self::CLOSED => 3,
        };
    }

    public static function fromDbValue(int|string|null $value): self
    {
        return match ((int) $value) {
            0 => self::PENDING,
            1 => self::ASSIGNED,
            2 => self::IN_PROGRESS,
            3 => self::CLOSED,
            default => self::PENDING,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ASSIGNED => 'Assigned',
            self::IN_PROGRESS => 'In progress',
            self::CLOSED => 'Closed',
        };
    }
}
