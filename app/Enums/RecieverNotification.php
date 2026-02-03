<?php

namespace App\Enums;

enum RecieverNotification : int {
    case ALL = 1;
    case CLIENT = 2;
    case KTV = 3;
    case AGENCY = 4;

    public function label(): string
    {
        return match ($this) {
            self::ALL => __('admin.mobile_notification.receiver.all'),
            self::CLIENT => __('admin.mobile_notification.receiver.client'),
            self::KTV => __('admin.mobile_notification.receiver.ktv'),
            self::AGENCY => __('admin.mobile_notification.receiver.agency'),
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

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getLabel(int $value): string
    {
        return self::tryFrom($value)?->label() ?? '';
    }
}