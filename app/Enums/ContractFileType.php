<?php

namespace App\Enums;

enum ContractFileType: int
{
    case TERM_OF_USE = 1;
    case POLICY_REGISTER = 2;
    case POLICY_PRIVACY = 3;

    public static function getSlug(int $case): string
    {
        return match ($case) {
            self::TERM_OF_USE->value => 'term-of-use',
            self::POLICY_REGISTER->value => 'policy-register',
            self::POLICY_PRIVACY->value => 'policy-privacy',
        };
    }


    public function label(): string
    {
        return match ($this) {
            self::TERM_OF_USE => __('admin.common.contract_file_type.term_of_use'),
            self::POLICY_REGISTER => __('admin.common.contract_file_type.policy_register'),
            self::POLICY_PRIVACY => __('admin.common.contract_file_type.policy_privacy'),
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
