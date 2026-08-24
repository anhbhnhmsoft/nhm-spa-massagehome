<?php

namespace App\Enums;

/**
 * Enum cho trạng thái Lời mời KTV Chủ động Matching (Proactive Invitation Status).
 * Dùng Backed Enum (int) map với database theo quy chuẩn MasaHome.
 */
enum InvitationStatus: int
{
    case PENDING = 1;
    case ACCEPTED = 2;
    case DECLINED = 3;
    case EXPIRED = 4;
    case CANCELED_BY_ADMIN = 5;

    /**
     * Trả về nhãn đa ngôn ngữ (Localization)
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('admin.invitation_status.pending'),
            self::ACCEPTED => __('admin.invitation_status.accepted'),
            self::DECLINED => __('admin.invitation_status.declined'),
            self::EXPIRED => __('admin.invitation_status.expired'),
            self::CANCELED_BY_ADMIN => __('admin.invitation_status.canceled_by_admin'),
        };
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
