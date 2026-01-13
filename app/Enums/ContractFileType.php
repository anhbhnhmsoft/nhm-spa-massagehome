<?php

namespace App\Enums;

enum ContractFileType: int
{
    case TERM_OF_USE = 1; // Hợp đồng sử dụng
    case POLICY_REGISTER = 2;    // Hợp đồng đăng ký
    case POLICY_PRIVACY = 3;    // Hợp đồng quyền riêng tư
    case POLICY_FOR_KTV = 4;    // Hợp đồng cho KTV
    case POLICY_FOR_AGENCY = 5;    // Hợp đồng cho đại lý

    public static function getSlug(int $case): string
    {
        return match ($case) {
            self::TERM_OF_USE->value => 'term-of-use',
            self::POLICY_REGISTER->value => 'policy-register',
            self::POLICY_PRIVACY->value => 'policy-privacy',
            self::POLICY_FOR_KTV->value => 'policy-for-ktv',
            self::POLICY_FOR_AGENCY->value => 'policy-for-agency',
        };
    }


    public function label(): string
    {
        return match ($this) {
            self::TERM_OF_USE => __('admin.common.contract_file_type.term_of_use'),
            self::POLICY_REGISTER => __('admin.common.contract_file_type.policy_register'),
            self::POLICY_PRIVACY => __('admin.common.contract_file_type.policy_privacy'),
            self::POLICY_FOR_KTV => __('admin.common.contract_file_type.policy_for_ktv'),
            self::POLICY_FOR_AGENCY => __('admin.common.contract_file_type.policy_for_agency'),
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
}
