<?php

namespace App\Enums;

enum BannerType: int
{
    case HOMEPAGE = 1; // Banner hiển thị trên trang chủ
    case AFFILIATE_CUSTOMER = 2; // Banner hiển thị trên trang affiliate customer
    case AFFILIATE_KTV = 3; // Banner hiển thị trên trang affiliate KTV
    case AFFILIATE_AGENCY = 4; // Banner hiển thị trên trang affiliate agency

    public function label()
    {
        return match ($this) {
            self::HOMEPAGE => __('admin.banner.type.HOMEPAGE'),
            self::AFFILIATE_CUSTOMER => __('admin.banner.type.AFFILIATE_CUSTOMER'),
            self::AFFILIATE_KTV => __('admin.banner.type.AFFILIATE_KTV'),
            self::AFFILIATE_AGENCY => __('admin.banner.type.AFFILIATE_AGENCY'),
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
}
