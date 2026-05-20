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
            self::PENDING => __('admin.support_ticket.status.pending'),
            self::ASSIGNED => __('admin.support_ticket.status.assigned'),
            self::IN_PROGRESS => __('admin.support_ticket.status.in_progress'),
            self::CLOSED => __('admin.support_ticket.status.closed'),
        };
    }

    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->dbValue()] = $case->label();
        }

        return $options;
    }

    public static function toOptions(): array
    {
        return self::options();
    }
}
