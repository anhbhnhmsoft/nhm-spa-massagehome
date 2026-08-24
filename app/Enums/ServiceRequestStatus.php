<?php

namespace App\Enums;

/**
 * Enum cho trạng thái Yêu cầu dịch vụ (Service Request Status).
 * Dùng Backed Enum (int) map với database theo quy chuẩn MasaHome.
 */
enum ServiceRequestStatus: int
{
    case NEW = 1;
    case ASSIGNED = 2;
    case SEARCHING_KTV = 3;
    case PROPOSAL_SENT = 4;
    case WAITING_CUSTOMER_CONFIRM = 5;
    case MATCHED = 6;
    case BOOKING_CREATED = 7;
    case CLOSED = 8;
    case CANCELED = 9;

    /**
     * Trả về nhãn đa ngôn ngữ (Localization)
     */
    public function label(): string
    {
        return match ($this) {
            self::NEW => __('admin.service_request_status.new'),
            self::ASSIGNED => __('admin.service_request_status.assigned'),
            self::SEARCHING_KTV => __('admin.service_request_status.searching_ktv'),
            self::PROPOSAL_SENT => __('admin.service_request_status.proposal_sent'),
            self::WAITING_CUSTOMER_CONFIRM => __('admin.service_request_status.waiting_customer_confirm'),
            self::MATCHED => __('admin.service_request_status.matched'),
            self::BOOKING_CREATED => __('admin.service_request_status.booking_created'),
            self::CLOSED => __('admin.service_request_status.closed'),
            self::CANCELED => __('admin.service_request_status.canceled'),
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
