<?php


namespace App\Enums;

enum WalletTransactionStatus: int
{
    case PENDING = 1; // Chờ xử lý
    case COMPLETED = 2; // (Thành công)
    case FAILED = 3; // (Thất bại)
    case CANCELLED = 4; // (Hủy)
    case REFUNDED = 5; // (Trả lại)

    // Trạng thái nạp vào ví
    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('admin.transaction.status.PENDING'),
            self::COMPLETED => __('admin.transaction.status.COMPLETED'),
            self::FAILED => __('admin.transaction.status.FAILED'),
            self::CANCELLED => __('admin.transaction.status.CANCELLED'),
            self::REFUNDED => __('admin.transaction.status.REFUNDED'),
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
