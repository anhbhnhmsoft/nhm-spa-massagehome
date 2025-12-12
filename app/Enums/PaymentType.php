<?php

namespace App\Enums;

enum PaymentType: int
{
    case QR_BANKING = 1; // Thanh toán qua mã QR
    case ZALO_PAY = 2; // Thanh toán qua Zalo Pay
    case MOMO_PAY = 3; // Thanh toán qua Momo Pay

    public function label(): string
    {
        return match ($this) {
            self::QR_BANKING => __('admin.booking.payment_type.qr_banking'),
            self::ZALO_PAY => __('admin.booking.payment_type.zalo_pay'),
            self::MOMO_PAY => __('admin.booking.payment_type.momo_pay'),
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
}
