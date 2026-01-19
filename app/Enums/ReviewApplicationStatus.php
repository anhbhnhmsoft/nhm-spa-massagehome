<?php

namespace App\Enums;

/**
 * Enum cho trạng thái đánh giá KTV, Agency
 */
enum ReviewApplicationStatus: int
{
    case PENDING = 1; // Chờ duyệt
    case APPROVED = 2; // Duyệt
    case REJECTED = 3; // Từ chối

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('admin.ktv_apply.status.pending'),
            self::APPROVED => __('admin.ktv_apply.status.approved'),
            self::REJECTED => __('admin.ktv_apply.status.rejected'),
        };
    }
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function toOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    public static function getLabel(int $value): string
    {
        return self::tryFrom($value)?->label() ?? '';
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }

    public static function getColor(int $value): string
    {
        return self::tryFrom($value)?->color() ?? '';
    }
}
