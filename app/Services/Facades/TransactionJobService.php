<?php

namespace App\Services\Facades;

use App\Core\Helper;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\ConfigName;
use App\Enums\NotificationType;
use App\Enums\PaymentType;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Jobs\SendNotificationJob;
use App\Models\User;
use App\Repositories\BookingRepository;
use App\Repositories\CouponRepository;
use App\Repositories\CouponUsedRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use App\Services\BookingService;
use App\Services\CouponService;
use App\Services\UserWithdrawInfoService;
use App\Services\Validator\BookingValidator;
use App\Services\Validator\CouponValidator;
use App\Services\Validator\WalletValidator;
use App\Services\WalletService;
use App\Services\ConfigService;
use Illuminate\Support\Facades\DB;
use Throwable;

class TransactionJobService extends BaseService
{
    public function __construct(
        protected BookingRepository           $bookingRepository,
        protected WalletRepository            $walletRepository,
        protected WalletTransactionRepository $walletTransactionRepository,
        protected CouponRepository            $couponRepository,
        protected BookingValidator            $bookingValidator,
        protected CouponValidator             $couponValidator,
        protected WalletValidator             $walletValidator,

        protected BookingService              $bookingService,
        protected WalletService               $walletService,
        protected ConfigService               $configService,
        protected CouponService               $couponService,
        protected UserWithdrawInfoService     $userWithdrawInfoService,


    )
    {
        parent::__construct();
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
        return $this->execute(
            callback: function () use ($bookingId) {
                // Lấy tỉ lệ đổi tiền từ config
                $exchangeRate = (float)$this->configService->getConfigValue(ConfigName::CURRENCY_EXCHANGE_RATE);

                // Tìm booking
                $booking = $this->bookingRepository
                    ->getBookingByIdAndStatus($bookingId, BookingStatus::PENDING);
                if (!$booking) {
                    throw new ServiceException(
                        message: __("error.booking_not_found")
                    );
                }

                /**
                 * Lấy thông tin khách hàng
                 * @var User $customer
                 */
                $customer = $booking->user;

                // Kiểm tra Coupon có được sử dụng hay không
                if (!empty($booking->coupon_id)) {
                    // Khóa dòng Coupon để đảm bảo tính Atomic cho used_count và config JSON
                    $coupon = $this->couponRepository->getCouponByIdOrFail(
                        couponId: $booking->coupon_id,
                        lockForUpdate: true,
                    );

                    if (!$coupon) {
                        throw new ServiceException(__("booking.coupon.not_found"));
                    }
                    // Kiểm tra Coupon có hợp lệ không
                    $this->couponValidator->validateUseCoupon(
                        coupon: $coupon,
                        user: $customer,
                    );

                    // Trường hợp TH1: CHƯA sở hữu (Khách dùng trực tiếp từ coupon gợi ý)
                    if (!$customer->collectionCoupons()
                        ->where('coupon_id', $coupon->id)
                        ->exists()
                    ) {
                        // Tăng số lượng thu thập trong ngày (vì họ vừa dùng vừa "nhặt")
                        $coupon->increment('count_collect');
                        // Thêm vào ví và đánh dấu đã dùng
                        $customer->collectionCoupons()->syncWithoutDetaching([
                            $coupon->id => ['is_used' => true]
                        ]);
                    } // Trường hợp TH2: ĐÃ sở hữu (Đã nhặt vào ví trước đó)
                    else {
                        // Cập nhật trạng thái trong ví thành đã dùng
                        $customer->collectionCoupons()->updateExistingPivot($coupon->id, ['is_used' => true]);
                    }

                    // Tăng lượt sử dụng thực tế (used_count)
                    $coupon->increment('used_count');

                    // Ghi lịch sử giao dịch (Bảng coupon_used)
                    $coupon->couponUseds()->create([
                        'user_id' => $customer->id,
                        'booking_id' => $bookingId,
                    ]);
                }


                // Kiểm tra số dư ví của khách hàng có đủ không
                $walletCustomer = $this->walletRepository->getWalletByUserId(
                    userId: $customer->id,
                    lockForUpdate: true,
                );
                if (!$walletCustomer) {
                    throw new ServiceException(
                        message: __("booking.payment.wallet_customer_not_found")
                    );
                }
                // Kiểm tra số dư ví của khách hàng có đủ không
                $this->walletValidator->validateBookingBalance(
                    wallet: $walletCustomer,
                    price: $booking->price,
                    priceDistance: $booking->price_transportation,
                    couponDiscount: $booking->price_discount ?? 0,
                );

                $walletKtv = $this->walletRepository->getWalletByUserId(
                    userId: $booking->ktv_user_id,
                    lockForUpdate: true,
                );
                if (!$walletKtv) {
                    throw new ServiceException(
                        message: __("booking.payment.wallet_technician_not_found")
                    );
                }

                // Tạo transaction phí dịch vụ cho Khách hàng
                $balanceAfterBookingService = $walletCustomer->balance - ($booking->price - ($booking->price_discount ?? 0));
                $this->walletTransactionRepository->create([
                    'wallet_id' => $walletCustomer->id,
                    'foreign_key' => $bookingId,
                    'money_amount' => $booking->price * $exchangeRate,
                    'exchange_rate_point' => $exchangeRate,
                    'point_amount' => $booking->price,
                    'balance_after' => $balanceAfterBookingService,
                    'type' => WalletTransactionType::PAYMENT->value,
                    'status' => WalletTransactionStatus::COMPLETED->value,
                    'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                    'expired_at' => now(),
                ]);
                // Tạo transaction phí di chuyển cho Khách hàng
                $balanceAfterTransportation = $balanceAfterBookingService - $booking->price_transportation;
                $this->walletTransactionRepository->create([
                    'wallet_id' => $walletCustomer->id,
                    'foreign_key' => $bookingId,
                    'money_amount' => $booking->price_transportation * $exchangeRate,
                    'exchange_rate_point' => $exchangeRate,
                    'point_amount' => $booking->price_transportation,
                    'balance_after' => $balanceAfterTransportation,
                    'type' => WalletTransactionType::FEE_TRANSPORT->value,
                    'status' => WalletTransactionStatus::COMPLETED->value,
                    'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                    'expired_at' => now(),
                ]);
                // Cập nhật số dư ví của khách hàng
                $walletCustomer->balance = $balanceAfterTransportation;
                $walletCustomer->save();

                // Tạo transaction nhận tiền phí di chuyển cho KTV
                $balanceKtvAfterEarTransportation = $walletKtv->balance + $booking->price_transportation;
                $this->walletTransactionRepository->create([
                    'wallet_id' => $walletKtv->id,
                    'foreign_key' => $bookingId,
                    'money_amount' => $booking->price_transportation * $exchangeRate,
                    'exchange_rate_point' => $exchangeRate,
                    'point_amount' => $booking->price_transportation,
                    'balance_after' => $balanceKtvAfterEarTransportation,
                    'type' => WalletTransactionType::EARN_TRANSPORT->value,
                    'status' => WalletTransactionStatus::COMPLETED->value,
                    'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                    'expired_at' => now(),
                ]);

                // Cập nhật số dư ví của KTV
                $walletKtv->balance = $balanceKtvAfterEarTransportation;
                $walletKtv->save();

                // Cập nhật trạng thái booking thành COMPLETED
                $booking->status = BookingStatus::CONFIRMED->value;
                $booking->save();


                // Bắn notif cho người dùng khi đặt lịch thành công
                SendNotificationJob::dispatch(
                    userId: $booking->user_id,
                    type: NotificationType::BOOKING_SUCCESS,
                    data: [
                        'booking_id' => $booking->id,
                        'category_id' => $booking->category_id,
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
            },
            useTransaction: true
        );
    }

    /**
     * Xử lý khi confirm lịch hẹn thất bại
     * @param string $bookingId
     * @param string|null $reason
     * @return ServiceReturn
     */
    public function handleFailedConfirmBooking(string $bookingId, string $reason = null): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($bookingId, $reason) {
                $reasonCancel = "System: " . $reason ?? __("error.system_booking_failed");

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
            },
            useTransaction: true
        );
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
                bookingId: $booking->id,
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
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi TransactionJobService@handleFinishBooking",
                ex: $exception
            );
            return ServiceReturn::error($exception->getMessage());
        }
    }

    /**
     * Xử lý khi hủy booking
     * @param string $bookingId
     * @param array $data
     * @return ServiceReturn
     * @throws Throwable
     */
    public function handleConfirmCancelBooking(
        string $bookingId,
        array  $data
    ): ServiceReturn
    {
        try {
            $booking = $this->bookingService->getBookingRepository()->query()
                ->where('id', $bookingId)
                ->where('status', BookingStatus::WAITING_CANCEL->value)
                ->first();
            if (!$booking) {
                throw new ServiceException(__("error.not_found_booking"));
            }

            return $this->bookingService->approveCancel($booking, $data);
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi TransactionJobService@handleConfirmCancelBooking",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Xử lý điều phối booking sang KTV khác
     * @param int $bookingId
     * @param int|null $newServiceId
     * @param int|null $newKtvId
     * @return ServiceReturn
     * @throws Throwable
     */
    public function handleReassignBooking(
        int  $bookingId,
        ?int $newServiceId,
        ?int $newKtvId
    ): ServiceReturn
    {
        DB::beginTransaction();
        try {
            if (!$newServiceId || !$newKtvId) {
                throw new ServiceException(__("error.invalid_data"));
            }

            // Tìm booking với lock để tránh race condition
            $booking = $this->bookingService->getBookingRepository()->query()
                ->where('id', $bookingId)
                ->where('status', BookingStatus::WAITING_CANCEL->value)
                ->lockForUpdate()
                ->first();

            if (!$booking) {
                throw new ServiceException(__("error.booking_not_found_or_invalid_status"));
            }

            // Lưu thông tin KTV cũ để gửi notification
            $oldKtvId = $booking->ktv_user_id;

            // Cập nhật booking sang KTV mới
            $booking->service_id = $newServiceId;
            $booking->ktv_user_id = $newKtvId;
            $booking->status = BookingStatus::CONFIRMED->value;
            $booking->reason_cancel = null; // Xóa lý do hủy
            $booking->cancel_by = null;
            $booking->save();

            DB::commit();

            // Gửi notification cho khách hàng
            SendNotificationJob::dispatch(
                userId: $booking->user_id,
                type: NotificationType::BOOKING_REASSIGNED,
                data: [
                    'booking_id' => $booking->id,
                ]
            );

            // Gửi notification cho KTV mới
            SendNotificationJob::dispatch(
                userId: $newKtvId,
                type: NotificationType::NEW_BOOKING_REQUEST,
                data: [
                    'booking_id' => $booking->id,
                    'customer_name' => $booking->user->name ?? '',
                    'booking_time' => $booking->booking_time?->format('Y-m-d H:i:s'),
                ]
            );

            // Gửi notification cho KTV cũ
            if ($oldKtvId && $oldKtvId != $newKtvId) {
                SendNotificationJob::dispatch(
                    userId: $oldKtvId,
                    type: NotificationType::BOOKING_CANCELLED,
                    data: [
                        'booking_id' => $booking->id,
                        'reason' => __('booking.reassigned_to_other_ktv'),
                    ]
                );
            }

            return ServiceReturn::success(
                message: __("booking.reassign_success")
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi TransactionJobService@handleReassignBooking",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Xử lý Trả tiền thưởng cho người giới thiệu khi mời KTV thành công
     * @param  $referrerId - Id người giới thiệu
     * @param  $userId - Id nguời dc giới thiệu
     * @return ServiceReturn
     * @throws Throwable
     */
    public function handleRewardForKtvReferral(
        $referrerId,
        $userId
    )
    {

        DB::beginTransaction();
        try {
            if (!$referrerId || !$userId) {
                throw new ServiceException(__("error.invalid_data"));
            }
            // Lấy số tiền thưởng từ config
            $rewardAmount = (float)$this->configService->getConfigValue(ConfigName::KTV_REFERRAL_REWARD_AMOUNT);
            // Lấy tỷ giá đổi tiền
            $exchangeRate = (float)$this->configService->getConfigValue(ConfigName::CURRENCY_EXCHANGE_RATE);
            // Nếu = 0 thì tắt tính năng, không trả tiền
            if ($rewardAmount <= 0) {
                DB::commit();
                return ServiceReturn::success(
                    message: __('wallet.referral_reward_disabled')
                );
            }

            // Xử lý hoa hồng thưởng của người giới thiệu kỹ thuật viên
            $this->walletService->processRewardReferralKtvCommission(
                referrerId: $referrerId,
                userId: $userId,
                rewardAmount: $rewardAmount,
                exchangeRate: $exchangeRate,
            );

            DB::commit();
            return ServiceReturn::success();
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi TransactionJobService@handleRewardForKtvReferral",
                ex: $exception
            );
            return ServiceReturn::error($exception->getMessage());
        }
    }

    /**
     * Xử lý tạo thông tin rút tiền
     * @param $userId - Id người dùng
     * @param $withdrawInfoId - Id thông tin rút tiền
     * @param $amount - Số tiền cần rút
     * @param $withdrawMoney - Số tiền thực nhận
     * @param $feeWithdraw - Số tiền phí rút
     * @param $exchangeRate - Tỷ giá đổi tiền
     * @param $note - Ghi chú
     * @return ServiceReturn
     */
    public function handleCreateWithdrawRequest(
        $userId,
        $withdrawInfoId,
        $amount,
        $withdrawMoney,
        $feeWithdraw,
        $exchangeRate,
        $note = null
    ): ServiceReturn
    {
        DB::beginTransaction();
        try {
            if (!$userId || !$withdrawInfoId || !$amount || !$withdrawMoney || !$feeWithdraw || !$exchangeRate) {
                throw new ServiceException(__("error.invalid_data"));
            }

            // Lấy wallet
            $wallet = $this->walletService->getWalletByUserId(
                userId: $userId,
                lockForUpdate: true
            );
            if (!$wallet) {
                throw new ServiceException(message: __("error.wallet_not_found"));
            }

            // Tạo transaction pending
            $transactionWithdraw = $this->walletService->createWithdraw(
                walletCustomer: $wallet,
                withdrawInfoId: $withdrawInfoId,
                withdrawMoney: $withdrawMoney,
                exchangeRate: $exchangeRate,
                note: $note,
            );

            // Tạo transaction phí rút
            $this->walletService->createWithdrawFee(
                walletCustomer: $wallet,
                transactionId: $transactionWithdraw->id,
                feeAmount: $feeWithdraw,
                exchangeRate: $exchangeRate,
            );

            DB::commit();

            return ServiceReturn::success();
        } catch (\Throwable $exception) {
            LogHelper::error(
                message: "Lỗi TransactionJobService@handleCreateWithdrawRequest",
                ex: $exception
            );
            DB::rollBack();
            return ServiceReturn::error($exception->getMessage());
        }
    }

    /**
     * Xử lý xác nhận rút tiền
     * @param $transactionId - Id transaction rút tiền
     * @return ServiceReturn
     */
    public function handleConfirmWithdrawRequest($transactionId)
    {
        DB::beginTransaction();
        try {
            if (!$transactionId) {
                throw new ServiceException(__("error.invalid_data"));
            }

            // Lấy transaction rút tiền
            $transactionWithdraw = $this->walletService->getTransactionRepository()->getWithdrawPendingTransactionById(
                transactionId: $transactionId,
            );

            // Kiểm tra transaction rút tiền có tồn tại không và có phải là transaction rút tiền đang chờ duyệt không
            if (!$transactionWithdraw) {
                throw new ServiceException(message: __("error.transaction_not_found"));
            }
            // Lấy ví của người dùng
            $wallet = $this->walletService->getWalletByUserId(
                userId: $transactionWithdraw->wallet->user_id,
                lockForUpdate: true
            );
            if (!$wallet) {
                throw new ServiceException(message: __("error.wallet_not_found"));
            }

            // Lấy giao dịch phí rút tiền
            $transactionWithdrawFee = $this->walletService->getTransactionRepository()->query()
                ->where('wallet_id', $wallet->id)
                ->where('foreign_key', $transactionWithdraw->id)
                ->where('type', WalletTransactionType::FEE_WITHDRAW->value)
                ->first();

            // Kiểm tra transaction phí rút tiền có tồn tại không
            if (!$transactionWithdrawFee) {
                throw new ServiceException(message: __("error.transaction_not_found"));
            }

            // Xử lý xác nhận rút tiền
            $this->walletService->confirmWithdraw(
                transactionWithdraw: $transactionWithdraw,
                transactionWithdrawFee: $transactionWithdrawFee,
                wallet: $wallet,
            );

            DB::commit();

            SendNotificationJob::dispatch(
                userId: $wallet->user_id,
                type: NotificationType::WALLET_WITHDRAW,
            );

            return ServiceReturn::success();
        } catch (\Throwable $exception) {
            LogHelper::error(
                message: "Lỗi TransactionJobService@handleConfirmWithdrawRequest",
                ex: $exception
            );
            DB::rollBack();
            return ServiceReturn::error($exception->getMessage());
        }
    }

    /**
     * Xử lý hủy rút tiền
     * @param $transactionId - Id transaction rút tiền
     * @return ServiceReturn
     */
    public function handleCancelWithdrawRequest($transactionId)
    {
        DB::beginTransaction();
        try {
            if (!$transactionId) {
                throw new ServiceException(__("error.invalid_data"));
            }
            // Lấy transaction rút tiền
            $transactionWithdraw = $this->walletService->getTransactionRepository()->getWithdrawPendingTransactionById(
                transactionId: $transactionId,
            );

            // Kiểm tra transaction rút tiền có tồn tại không và có phải là transaction rút tiền đang chờ duyệt không
            if (!$transactionWithdraw) {
                throw new ServiceException(message: __("error.transaction_not_found"));
            }
            // Lấy ví của người dùng
            $wallet = $this->walletService->getWalletByUserId(
                userId: $transactionWithdraw->wallet->user_id,
                lockForUpdate: true
            );
            if (!$wallet) {
                throw new ServiceException(message: __("error.wallet_not_found"));
            }

            // Lấy giao dịch phí rút tiền
            $transactionWithdrawFee = $this->walletService->getTransactionRepository()->query()
                ->where('wallet_id', $wallet->id)
                ->where('foreign_key', $transactionWithdraw->id)
                ->where('type', WalletTransactionType::FEE_WITHDRAW->value)
                ->first();

            // Kiểm tra transaction phí rút tiền có tồn tại không
            if (!$transactionWithdrawFee) {
                throw new ServiceException(message: __("error.transaction_not_found"));
            }

            // Xử lý hủy rút tiền
            $this->walletService->cancelWithdraw(
                transactionWithdraw: $transactionWithdraw,
                transactionWithdrawFee: $transactionWithdrawFee,
                wallet: $wallet,
            );

            DB::commit();

            return ServiceReturn::success();
        } catch (\Throwable $exception) {
            LogHelper::error(
                message: "Lỗi TransactionJobService@handleCancelWithdrawRequest",
                ex: $exception
            );
            DB::rollBack();
            return ServiceReturn::error($exception->getMessage());
        }
    }
}
