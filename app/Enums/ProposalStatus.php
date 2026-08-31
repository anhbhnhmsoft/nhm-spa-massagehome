<?php

namespace App\Enums;

/**
 * Enum cho trạng thái Đề xuất KTV (Proposal Status).
 * Dùng Backed Enum (int) map với database theo quy chuẩn MasaHome.
 */
enum ProposalStatus: int
{
    case PROPOSED = 1;
    case KTV_ACCEPTED = 2;
    case KTV_DECLINED = 3;
    case CUSTOMER_ACCEPTED = 4;
    case CUSTOMER_DECLINED = 5;
    case EXPIRED = 6;

    /**
     * Trả về nhãn đa ngôn ngữ (Localization)
     */
    public function label(): string
    {
        return match ($this) {
            self::PROPOSED => __('admin.proposal_status.proposed'),
            self::KTV_ACCEPTED => __('admin.proposal_status.ktv_accepted'),
            self::KTV_DECLINED => __('admin.proposal_status.ktv_declined'),
            self::CUSTOMER_ACCEPTED => __('admin.proposal_status.customer_accepted'),
            self::CUSTOMER_DECLINED => __('admin.proposal_status.customer_declined'),
            self::EXPIRED => __('admin.proposal_status.expired'),
        };
    }

    /**
     * Màu sắc hiển thị badge
     */
    public function color(): string
    {
        return match ($this) {
            self::PROPOSED => 'info',
            self::KTV_ACCEPTED => 'warning',
            self::CUSTOMER_ACCEPTED => 'success',
            self::KTV_DECLINED, self::CUSTOMER_DECLINED => 'danger',
            self::EXPIRED => 'gray',
        };
    }

    /**
     * Lấy màu sắc an toàn từ giá trị thô (int)
     */
    public static function getColor(?int $value): string
    {
        if (is_null($value)) {
            return 'gray';
        }
        return self::tryFrom($value)?->color() ?? 'gray';
    }

    /**
     * Chuyển đổi danh sách Enum thành mảng Select Options cho Filament Form
     */
    public static function toOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    /**
     * Lấy nhãn hiển thị an toàn từ giá trị thô (int)
     */
    public static function getLabel(?int $value): string
    {
        if (is_null($value)) {
            return '';
        }
        return self::tryFrom($value)?->label() ?? '';
    }

    /**
     * Trả về mảng tất cả giá trị Enum
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
