<?php

namespace App\Enums;

enum PaymentType: int
{
    case QR_BANKING = 1; // Thanh toán qua mã QR
    case ZALO_PAY = 2; // Thanh toán qua Zalo Pay
    case MOMO_PAY = 3; // Thanh toán qua Momo Pay
    case BY_POINTS = 4; // Thanh toán qua điểmwallet_transactions
    case WITHDRAWAL = 5; // Rút tiềnwallets

    public function label(): string
    {
        return match ($this) {
            self::QR_BANKING => __('admin.booking.payment_type.qr_banking'),
            self::ZALO_PAY => __('admin.booking.payment_type.zalo_pay'),
            self::MOMO_PAY => __('admin.booking.payment_type.momo_pay'),
            self::BY_POINTS => __('admin.booking.payment_type.by_points'),
            self::WITHDRAWAL => __('admin.booking.payment_type.withdrawal'),
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
