<?php

namespace App\Services\Facades;

use App\Core\Helper;
use App\Core\LogHelper;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\ConfigName;
use App\Enums\NotificationType;
use App\Jobs\SendNotificationJob;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CouponService;
use App\Services\WalletService;
use App\Services\ConfigService;
use Illuminate\Support\Facades\DB;
use Throwable;

class TransactionJobService
{
    public function __construct(
        protected BookingService $bookingService,
        protected WalletService  $walletService,
        protected ConfigService  $configService,
        protected CouponService  $couponService,
    )
    {
    }

    /**
     * Xác nhận đặt lịch cho khách hàng
     * @param int $bookingId
     * @return ServiceReturn
     * @throws Throwable
     */
    public function handleConfirmBooking(
        int $bookingId,
    ): ServiceReturn
    {
        DB::beginTransaction();
        try {
            // Lấy tỉ lệ đổi tiền từ config
            $exchangeRate = (float)$this->configService->getConfigValue(ConfigName::CURRENCY_EXCHANGE_RATE);

            // Tìm booking theo id
            $booking = $this->bookingService->getBookingRepository()->query()->find($bookingId);
            if (!$booking) {
                throw new ServiceException(
                    message: __("error.booking_not_found")
                );
            }

            // Kiểm tra số dư ví khách hàng có đủ không
            $walletCustomerCheck = $this->walletService->checkUserWalletBalance(
                userId: $booking->user_id,
                price: $booking->price,
                lockForUpdate: true,
            );

            // Nếu số dư ví khách hàng không đủ
            if (!$walletCustomerCheck['is_enough']) {
                throw new ServiceException(
                    message: __("error.wallet_customer_not_enough")
                );
            }

            // Lấy ví khách hàng
            $walletCustomer = $walletCustomerCheck['wallet'];

            // Kiểm tra coupon có được sử dụng hay không
            if ($booking->coupon_id) {
                $this->couponService->useCoupon(
                    couponId: $booking->coupon_id,
                    userId: $booking->user_id,
                    serviceId: $booking->service_id,
                    bookingId: $booking->id,
                );
            }

            // tiến hành tạo transaction thanh toán booking và cập nhật số dư ví cho Khách hàng
            $this->walletService->createPaymentServiceBookingForCustomer(
                walletCustomer: $walletCustomer,
                bookingId: $bookingId,
                price: $booking->price,
                exchangeRate: $exchangeRate,
            );

            // Cập nhật trạng thái đặt lịch thành xác nhận
            $booking->status = BookingStatus::CONFIRMED->value;
            $booking->save();
            DB::commit();


            // Bắn notif cho người dùng khi đặt lịch thành công
            SendNotificationJob::dispatch(
                userId: $booking->user_id,
                type: NotificationType::BOOKING_SUCCESS,
                data: [
                    'booking_id' => $booking->id,
                    'service_id' => $booking->service_id,
                    'booking_time' => $booking->booking_time->format('Y-m-d H:i:s'),
                    'price' => $booking->price,
                ]
            );

            // Bắn notif cho KTV khi có lịch hẹn mới
            SendNotificationJob::dispatch(
                userId: $booking->ktv_user_id, // KTV
                type: NotificationType::NEW_BOOKING_REQUEST,
                data: [
                    'booking_id' => $booking->id,
                    'customer_name' => $booking->user->name,
                    'booking_time' => $booking->booking_time->format('Y-m-d H:i:s'),
                ]
            );

            // Thông báo thay đổi số dư ví cho khách hàng
            SendNotificationJob::dispatch(
                userId: $booking->user_id,
                type: NotificationType::PAYMENT_COMPLETE,
                data: [
                    'booking_id' => $booking->id,
                ]
            );

            return ServiceReturn::success(
                message: __("booking.payment.success")
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi TransactionJobService@handleConfirmBooking",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Xử lý khi thanh toán thất bại
     * @param string $bookingId
     * @return ServiceReturn
     */
    public function handleFailedConfirmBooking(string $bookingId): ServiceReturn
    {
        try {
            $reasonCancel = "System: " . __("error.system_booking_failed");

            $booking = $this->bookingService->getBookingRepository()
                ->query()
                ->lockForUpdate()
                ->find($bookingId);
            if (!$booking) {
                throw new ServiceException(
                    message: __("error.booking_not_found")
                );
            };

            // Kiểm tra nếu lịch đặt hẹn này đã bị hủy bởi hệ thống trước đó
            if ($booking->status == BookingStatus::PAYMENT_FAILED->value) {
                throw new ServiceException(
                    message: __("error.booking_already_failed")
                );
            }

            $booking->status = BookingStatus::PAYMENT_FAILED->value;
            $booking->reason_cancel = $reasonCancel;
            $booking->save();

            // Gửi thông báo cho khách hàng
            SendNotificationJob::dispatch(
                userId: $booking->user_id,
                type: NotificationType::BOOKING_CANCELLED,
                data: [
                    'booking_id' => $booking->id,
                    'reason' => $reasonCancel,
                ]
            );

            // Gửi thông báo cho KTV
            SendNotificationJob::dispatch(
                userId: $booking->ktv_user_id,
                type: NotificationType::BOOKING_CANCELLED,
                data: [
                    'booking_id' => $booking->id,
                    'reason' => $reasonCancel,
                ]
            );
            return ServiceReturn::success();
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi TransactionJobService@handleFailedConfirmBooking",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Xử lý khi hoàn thành booking
     * @param string $bookingId
     * @return ServiceReturn
     * @throws Throwable
     */
    public function handleFinishBooking(string $bookingId): ServiceReturn
    {
        DB::beginTransaction();
        try {
            // lấy mức chiết khấu của nhà cung cấp
            $discountRate = (float)$this->configService->getConfigValue(ConfigName::DISCOUNT_RATE);
            // Lấy tỷ giá đổi tiền
            $exchangeRate = (float)$this->configService->getConfigValue(ConfigName::CURRENCY_EXCHANGE_RATE);

            // Lấy thông tin booking
            $booking = $this->bookingService->getBookingRepository()->query()
                ->where('id', $bookingId)
                ->where('status', BookingStatus::COMPLETED->value)
                ->lockForUpdate()
                ->first();
            if (!$booking) {
                throw new ServiceException(__("error.not_found_booking"));
            }

            // Lấy ví của KTV
            $walletKtv = $this->walletService->getWalletByUserId($booking->ktv_user_id, true);
            if (!$walletKtv) {
                throw new ServiceException(
                    message: __("error.wallet_not_found")
                );
            }

            // Thanh toán cho KTV
            $this->walletService->createPaymentServiceBookingForKtv(
                walletKtv: $walletKtv,
                bookingId: $booking,
                price: $booking->price,
                exchangeRate: $exchangeRate,
                discountRate: $discountRate,
            );

            /**
             * @var User $customer - Khách hàng
             * @var User $staff - Kỹ thuật viên
             */
            $customer = $booking->user;
            $staff = $booking->ktvUser;

            // Giá trị thực tế hệ thống nhận về
            $systemIncome = Helper::calculateSystemMinus($booking->price, $discountRate);


            // xử lý hoa hồng cho khách hàng
            if ($customer->referred_by_user_id) {
                $this->walletService->processAffiliateCommission(
                    referrerId: $customer->referred_by_user_id,
                    systemIncome: $systemIncome,
                    bookingId: $bookingId,
                    exchangeRate: $exchangeRate,
                );
            }

            // xử lý hoa hồng cho nhân viên
            if ($staff->referred_by_user_id) {
                $this->walletService->processAffiliateCommission(
                    referrerId: $staff->referred_by_user_id,
                    systemIncome: $systemIncome,
                    bookingId: $bookingId,
                    exchangeRate: $exchangeRate,
                );
            }

            // xử lý hoa hồng cho người giới thiệu kỹ thuật viên (KTV/ Agency)
            if ($staff->reviewApplication->referrer_id) {
                $this->walletService->processReferralKtvCommission(
                    referrerId: $staff->reviewApplication->referrer_id,
                    systemIncome: $systemIncome,
                    bookingId: $bookingId,
                    exchangeRate: $exchangeRate,
                );
            }

            DB::commit();

            // Thông báo thay đổi số dư ví cho kỹ thuật viên
            SendNotificationJob::dispatch(
                userId: $booking->ktv_user_id,
                type: NotificationType::PAYMENT_SERVICE_FOR_TECHNICIAN,
                data: [
                    'booking_id' => $booking->id,
                ]
            );
            return ServiceReturn::success();
        }
        catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi TransactionJobService@handleFinishBooking",
                ex: $exception
            );
            return ServiceReturn::error($exception->getMessage());
        }
    }


}
