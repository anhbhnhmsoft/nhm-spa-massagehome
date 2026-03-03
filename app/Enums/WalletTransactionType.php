<?php

namespace App\Enums;

use App\Core\Helper\EnumHelper;

enum WalletTransactionType: int
{
    use EnumHelper;
    case DEPOSIT_QR_CODE = 1; // Nạp tiền qua mã QR
    case DEPOSIT_ZALO_PAY = 2; // Nạp tiền qua Zalo Pay
    case DEPOSIT_MOMO_PAY = 3; // Nạp tiền qua Momo Pay
    case WITHDRAWAL = 4; // Rút tiền (Yêu cầu)
    case PAYMENT = 5; // Thanh toán (Booking)
    case AFFILIATE = 6; // Nhận hoa hồng
    case PAYMENT_FOR_KTV = 7; // Thanh toán cho KTV
    case REFUND = 8; // Hoàn tiền cho customer
    case RETRIEVE_PAYMENT_REFUND_KTV = 9; // thu hồi tiền thanh toán cho KTV khi hủy booking
    case REFERRAL_KTV = 10; // Nhận hoa hồng từ người giới thiệu KTV
    case REFERRAL_INVITE_KTV_REWARD = 11; // Nhận hoa hồng khi mời KTV thành công
    case DEPOSIT_WECHAT_PAY = 12; // Nạp tiền qua Wechat Pay
    case FEE_WITHDRAW = 13; // Chi phí rút tiền
    case FEE_TRANSPORT = 14; // Chi phí di chuyển (Trừ tiền KH)
    case EARN_TRANSPORT = 15; // Nhận tiền từ di chuyển ( Cộng tiền KTV)


    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT_QR_CODE => __('admin.transaction.type.DEPOSIT_QR_CODE'),
            self::DEPOSIT_ZALO_PAY => __('admin.transaction.type.DEPOSIT_ZALO_PAY'),
            self::DEPOSIT_MOMO_PAY => __('admin.transaction.type.DEPOSIT_MOMO_PAY'),
            self::WITHDRAWAL => __('admin.transaction.type.WITHDRAWAL'),
            self::PAYMENT => __('admin.transaction.type.PAYMENT'),
            self::AFFILIATE => __('admin.transaction.type.AFFILIATE'),
            self::PAYMENT_FOR_KTV => __('admin.transaction.type.PAYMENT_FOR_KTV'),
            self::REFUND => __('admin.transaction.type.REFUND'),
            self::RETRIEVE_PAYMENT_REFUND_KTV => __('admin.transaction.type.RETRIEVE_PAYMENT_REFUND_KTV'),
            self::REFERRAL_KTV => __('admin.transaction.type.REFERRAL_KTV'),
            self::REFERRAL_INVITE_KTV_REWARD => __('admin.transaction.type.REFERRAL_INVITE_KTV_REWARD'),
            self::DEPOSIT_WECHAT_PAY => __('admin.transaction.type.DEPOSIT_WECHAT_PAY'),
            self::FEE_WITHDRAW => __('admin.transaction.type.FEE_WITHDRAW'),
            self::FEE_TRANSPORT => __('admin.transaction.type.FEE_TRANSPORT'),
            self::EARN_TRANSPORT => __('admin.transaction.type.EARN_TRANSPORT'),
        };
    }


    /**
     * Trạng thái nạp vào hệ thống
     * @return array
     */
    public static function incomeStatus(): array
    {
        return [
            self::DEPOSIT_QR_CODE->value,
            self::DEPOSIT_ZALO_PAY->value,
            self::DEPOSIT_MOMO_PAY->value,
            self::DEPOSIT_WECHAT_PAY->value,
            self::FEE_WITHDRAW->value,
        ];
    }

    /**
     * Trạng thái rút ra khỏi hệ thống
     * @return array
     */
    public static function outComeStatus()
    {
        return [
            self::WITHDRAWAL->value,
        ];
    }

    /**
     * Trạng thái doanh thu
     * @return array
     */
    public static function revenueStatus(): array
    {
        return [
            self::PAYMENT->value,
        ];
    }

    /**
     * Trạng thái chi phí vận hành
     * @return array
     */
    public static function operationCostStatus(): array
    {
        return [
            self::AFFILIATE->value,
            self::PAYMENT_FOR_KTV->value,
            self::REFERRAL_KTV->value,
            self::REFERRAL_INVITE_KTV_REWARD->value,
        ];
    }

    /**
     * Trạng thái chi phí vận chuyển
     * @return array
     */
    public static function transportStatus(): array
    {
        return [
            self::FEE_TRANSPORT->value,
        ];
    }

    /**
     * Trạng thái chi phí dịch vụ khách hàng
     * @return array
     */
    public static function customerCostStatus(): array
    {
        return [
            self::AFFILIATE->value,
        ];
    }

    /**
     * Trạng thái chi phí dịch vụ đại lý
     * @return array
     */
    public static function agencyCostStatus(): array
    {
        return [
            self::AFFILIATE->value,
            self::REFERRAL_KTV->value,
            self::REFERRAL_INVITE_KTV_REWARD->value,
        ];
    }

    /**
     * Trạng thái chi phí dịch vụ kỹ thuật viên
     * @return array
     */
    public static function technicalCostStatus(): array
    {
        return [
            self::AFFILIATE->value,
            self::REFERRAL_KTV->value,
            self::REFERRAL_INVITE_KTV_REWARD->value,
            self::PAYMENT_FOR_KTV->value,
        ];
    }


    // Lấy danh sách trạng thái giao dịch nạp vào ví
    public static function statusIn()
    {
        return [
            self::DEPOSIT_QR_CODE->value,
            self::DEPOSIT_ZALO_PAY->value,
            self::DEPOSIT_MOMO_PAY->value,
            self::DEPOSIT_WECHAT_PAY->value,
            self::AFFILIATE->value,
            self::PAYMENT_FOR_KTV->value,
            self::REFERRAL_INVITE_KTV_REWARD->value,
            self::REFERRAL_KTV->value,
        ];
    }


}
