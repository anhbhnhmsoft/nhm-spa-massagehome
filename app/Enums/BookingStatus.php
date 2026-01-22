<?php

namespace App\Enums;

enum BookingStatus: int
{
    case PENDING = 1; // Chờ xác nhận
    case CONFIRMED = 2; // Đã xác nhận
    case ONGOING = 3; // Đang diễn ra
    case COMPLETED = 4; // Đã hoàn thành
    case WAITING_CANCEL = 7; // Chờ hủy
    case CANCELED = 5; // Đã hủy
    case PAYMENT_FAILED = 6; // Thanh toán thất bại

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('admin.booking.status.pending'),
            self::CONFIRMED => __('admin.booking.status.confirmed'),
            self::ONGOING => __('admin.booking.status.ongoing'),
            self::COMPLETED => __('admin.booking.status.completed'),
            self::WAITING_CANCEL => __('admin.booking.status.waiting_cancel'),
            self::CANCELED => __('admin.booking.status.canceled'),
            self::PAYMENT_FAILED => __('admin.booking.status.payment_failed'),
        };
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

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getColor(int $value): string
    {
        return match ($value) {
            self::CONFIRMED->value => 'info',
            self::COMPLETED->value => 'success',
            self::WAITING_CANCEL->value => 'warning',
            self::CANCELED->value => 'danger',
            default => 'gray',
        };
    }


    public static function caseCanCancel(): array
    {
        return [
            self::PENDING->value,
            self::CONFIRMED->value,
        ];
    }
}
