<?php

namespace App\Enums;

enum UserFileType: int
{
    case IDENTITY_CARD_FRONT = 1;
    case IDENTITY_CARD_BACK = 2;
    case LICENSE = 3;
    case KTV_IMAGE_DISPLAY = 5;


    public function label(): string
    {
        return match ($this) {
            self::IDENTITY_CARD_FRONT => __('admin.ktv_apply.file_type.identity_card_front'),
            self::IDENTITY_CARD_BACK => __('admin.ktv_apply.file_type.identity_card_back'),
            self::LICENSE => __('admin.ktv_apply.file_type.license'),
            self::KTV_IMAGE_DISPLAY => __('admin.ktv_apply.file_type.ktv_image_display'),
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
