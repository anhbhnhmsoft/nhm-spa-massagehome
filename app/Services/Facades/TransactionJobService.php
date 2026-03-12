<?php

namespace App\Services\Facades;

use App\Core\Helper;
use App\Core\Helper\CalculatePrice;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\ConfigName;
use App\Enums\NotificationType;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Jobs\SendNotificationJob;
use App\Models\User;
use App\Repositories\BookingRepository;
use App\Repositories\CouponRepository;
use App\Repositories\CouponUsedRepository;
use App\Repositories\UserRepository;
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
        protected UserRepository              $userRepository,
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

                // Tạo transaction phí dịch vụ cho Khách hàng
                $this->walletTransactionRepository->create([
                    'wallet_id' => $walletCustomer->id,
                    'foreign_key' => $bookingId,
                    'money_amount' => $booking->price * $exchangeRate,
                    'exchange_rate_point' => $exchangeRate,
                    'point_amount' => $booking->price,
                    'type' => WalletTransactionType::PAYMENT->value,
                    'status' => WalletTransactionStatus::COMPLETED->value,
                    'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                    'expired_at' => now(),
                ]);
                // Tạo transaction phí di chuyển cho Khách hàng
                $this->walletTransactionRepository->create([
                    'wallet_id' => $walletCustomer->id,
                    'foreign_key' => $bookingId,
                    'money_amount' => $booking->price_transportation * $exchangeRate,
                    'exchange_rate_point' => $exchangeRate,
                    'point_amount' => $booking->price_transportation,
                    'type' => WalletTransactionType::PAYMENT_FEE_TRANSPORT->value,
                    'status' => WalletTransactionStatus::COMPLETED->value,
                    'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                    'expired_at' => now(),
                ]);
                // Tạo transaction hoàn tiền giảm giá cho Khách hàng
                if (($booking->price_discount ?? 0) > 0){
                    $this->walletTransactionRepository->create([
                        'wallet_id' => $walletCustomer->id,
                        'foreign_key' => $bookingId,
                        'money_amount' => $booking->price_discount * $exchangeRate,
                        'exchange_rate_point' => $exchangeRate,
                        'point_amount' => $booking->price_discount,
                        'type' => WalletTransactionType::SUBTRACT_MONEY_DISCOUNT_SERVICE->value,
                        'status' => WalletTransactionStatus::COMPLETED->value,
                        'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                        'expired_at' => now(),
                    ]);
                }
                // Tính tổng tiền phải thanh toán
                $totalBookingPrice = CalculatePrice::totalBookingPrice(
                    price: $booking->price,
                    priceDiscount: $booking->price_discount ?? 0,
                    priceTransportation: $booking->price_transportation,
                );
                // Cập nhật số dư ví của khách hàng
                $walletCustomer->balance -= $totalBookingPrice;
                $walletCustomer->save();


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
        return $this->execute(
            callback: function () use ($bookingId) {
                // lấy mức chiết khấu của nhà cung cấp
                $discountRate = (float)$this->configService->getConfigValue(ConfigName::DISCOUNT_RATE);
                // Lấy tỷ giá đổi tiền
                $exchangeRate = (float)$this->configService->getConfigValue(ConfigName::CURRENCY_EXCHANGE_RATE);

                // Lấy thông tin booking
                $booking = $this->bookingService->getBookingRepository()->query()
                    ->where('id', $bookingId)
                    ->where('status', BookingStatus::COMPLETED->value)
                    ->first();
                if (!$booking) {
                    throw new ServiceException(__("error.not_found_booking"));
                }

                // Lấy ví của KTV
                $walletKtv = $this->walletRepository->getWalletByUserId(
                    userId: $booking->ktv_user_id,
                    lockForUpdate: true,
                );
                if (!$walletKtv) {
                    throw new ServiceException(
                        message: __("booking.payment.wallet_technician_not_found")
                    );
                }

                //Tính số tiền mà kỹ thuật viên dc hưởng (trừ chiết khấu)
                $priceServiceEarned = CalculatePrice::calculatePriceDiscountForKTV($booking->price, $discountRate);

                // tạo transaction thanh toán dịch vụ cho kỹ thuật viên
                $this->walletTransactionRepository->create([
                    'wallet_id' => $walletKtv->id,
                    'foreign_key' => $booking->id,
                    'money_amount' => $priceServiceEarned * $exchangeRate,
                    'exchange_rate_point' => $exchangeRate,
                    'point_amount' => $priceServiceEarned,
                    'type' => WalletTransactionType::PAYMENT_FOR_KTV->value,
                    'status' => WalletTransactionStatus::COMPLETED->value,
                    'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                    'description' => __('booking.payment.wallet_technician'),
                    'expired_at' => null,
                    'metadata' => null,
                    'transaction_id' => null,
                ]);

                // tạo transaction thanh toán phí di chuyển cho kỹ thuật viên
                $this->walletTransactionRepository->create([
                    'wallet_id' => $walletKtv->id,
                    'foreign_key' => $booking->id,
                    'money_amount' => $booking->price_transportation * $exchangeRate,
                    'exchange_rate_point' => $exchangeRate,
                    'point_amount' => $booking->price_transportation,
                    'type' => WalletTransactionType::PAYMENT_KTV_EARN_TRANSPORT->value,
                    'status' => WalletTransactionStatus::COMPLETED->value,
                    'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                    'description' => __('booking.payment.wallet_technician'),
                    'expired_at' => null,
                    'metadata' => null,
                    'transaction_id' => null,
                ]);

                // Cộng số dư ví Kỹ thuật viên
                $walletKtv->balance += $priceServiceEarned + $booking->price_transportation;
                $walletKtv->save();

                /**
                 * @var User $customer - Khách hàng
                 * @var User $staff - Kỹ thuật viên
                 */
                $customer = $booking->user;
                $staff = $booking->ktvUser;

                // Giá trị thực tế hệ thống nhận về
                $systemIncome = CalculatePrice::calculateSystemMinus($booking->price, $discountRate);

                // xử lý hoa hồng cho khách hàng
                if (!empty($customer->referred_by_user_id)) {
                    $this->processAffiliateCommission(
                        referrerId: $customer->referred_by_user_id,
                        systemIncome: $systemIncome,
                        bookingId: $bookingId,
                        exchangeRate: $exchangeRate,
                    );
                }

                // xử lý hoa hồng cho nhân viên
                if (!empty($staff->referred_by_user_id)) {
                    $this->processAffiliateCommission(
                        referrerId: $staff->referred_by_user_id,
                        systemIncome: $systemIncome,
                        bookingId: $bookingId,
                        exchangeRate: $exchangeRate,
                    );
                }

                // xử lý hoa hồng cho người giới thiệu kỹ thuật viên (KTV/ Agency)
                if (!empty($staff->reviewApplication->referrer_id)) {
                    $this->processReferralKtvCommission(
                        referrerId: $staff->reviewApplication->referrer_id,
                        systemIncome: $systemIncome,
                        bookingId: $bookingId,
                        exchangeRate: $exchangeRate,
                    );
                }

                // Thông báo thay đổi số dư ví cho kỹ thuật viên
                SendNotificationJob::dispatch(
                    userId: $booking->ktv_user_id,
                    type: NotificationType::PAYMENT_SERVICE_FOR_TECHNICIAN,
                    data: [
                        'booking_id' => $booking->id,
                    ]
                );
                return true;
            },
            useTransaction: true,
            logServiceError: true,
        );
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
        return $this->execute(
            callback: function () use ($bookingId, $data) {
                $booking = $this->bookingService->getBookingRepository()->query()
                    ->where('id', $bookingId)
                    ->where('status', BookingStatus::WAITING_CANCEL->value)
                    ->first();
                if (!$booking) {
                    throw new ServiceException(__("error.not_found_booking"));
                }

                // chuyển trạng thái đơn hàng sang đã hủy
                $booking->status = BookingStatus::CANCELED->value;
                $booking->save();

                // Lấy ví khách hàng
                $clientWallet = $this->walletRepository->getWalletByUserId($booking->user_id);
                if (!$clientWallet) {
                    throw new ServiceException(
                        message: __("booking.payment.wallet_customer_not_found")
                    );
                }
                // Lấy transaction gốc để tham khảo (chỉ đọc, không sửa)
                $transactionOfCustomer = $this->walletTransactionRepository->query()
                    ->where("foreign_key", $booking->id)
                    ->where('type', WalletTransactionType::PAYMENT->value)
                    ->first();
                // Lấy transaction phí di chuyển để tham khảo (chỉ đọc, không sửa)
                $transactionTransportOfCustomer = $this->walletTransactionRepository->query()
                    ->where("foreign_key", $booking->id)
                    ->where('type', WalletTransactionType::PAYMENT_FEE_TRANSPORT->value)
                    ->first();

                if (!$transactionOfCustomer || !$transactionTransportOfCustomer) {
                    throw new ServiceException(
                        message: __("error.transaction_not_found")
                    );
                }



                // Lấy tỷ giá đổi tiền
                $exchangeRatePoint = $transactionOfCustomer->exchange_rate_point;
                $exchangeRatePointTransport = $transactionTransportOfCustomer->exchange_rate_point;

                // Số tiền hoàn tiền cho khách hàng
                if ($data['amount_pay_back_to_client'] && $data['amount_pay_back_to_client'] > 0) {
                    // tạo transaction hoàn số tiền dịch vụ cho khách hàng
                    $this->walletTransactionRepository->create([
                        'wallet_id' => $clientWallet->id,
                        'type' => WalletTransactionType::REFUND->value,
                        'point_amount' => $data['amount_pay_back_to_client'] * $exchangeRatePoint,
                        'money_amount' => $data['amount_pay_back_to_client'],
                        'exchange_rate_point' => $exchangeRatePoint,
                        'status' => WalletTransactionStatus::COMPLETED->value,
                        'transaction_code' => Helper::createDescPayment(PaymentType::REFUND),
                        'foreign_key' => $booking->id,
                        'description' => "Hoàn tiền booking #{$booking->id} - lý do: {$booking->reason_cancel}",
                        'expired_at' => now(),
                    ]);
                    $clientWallet->balance += $data['amount_pay_back_to_client'];
                } else {
                    // tạo transaction hoàn số tiền dịch vụ cho khách hàng và phí di chuyển
                    $this->walletTransactionRepository->create([
                        'wallet_id' => $clientWallet->id,
                        'type' => WalletTransactionType::REFUND->value,
                        'point_amount' => $transactionOfCustomer->point_amount * $exchangeRatePoint,
                        'money_amount' => $transactionOfCustomer->money_amount,
                        'exchange_rate_point' => $exchangeRatePoint,
                        'status' => WalletTransactionStatus::COMPLETED->value,
                        'transaction_code' => Helper::createDescPayment(PaymentType::REFUND),
                        'foreign_key' => $booking->id,
                        'description' => "Hoàn tiền booking #{$booking->id} - lý do: {$booking->reason_cancel}",
                        'expired_at' => now(),
                    ]);
                    // hoàn tiền phí di chuyển
                    $this->walletTransactionRepository->create([
                        'wallet_id' => $clientWallet->id,
                        'type' => WalletTransactionType::REFUND_CUSTOMER_TRANSPORT->value,
                        'point_amount' => $transactionTransportOfCustomer->point_amount * $exchangeRatePointTransport,
                        'money_amount' => $transactionTransportOfCustomer->money_amount,
                        'exchange_rate_point' => $exchangeRatePointTransport,
                        'status' => WalletTransactionStatus::COMPLETED->value,
                        'transaction_code' => Helper::createDescPayment(PaymentType::REFUND),
                        'foreign_key' => $booking->id,
                        'description' => "Hoàn tiền dịch vụ di chuyển  #{$booking->id} - lý do: {$booking->reason_cancel}",
                        'expired_at' => now(),
                    ]);

                    $clientWallet->balance += $transactionOfCustomer->point_amount + $transactionTransportOfCustomer->point_amount;

                    // Kiểm tra có giảm giá cho khách hàng không
                    if (($booking->price_discount ?? 0) > 0) {
                        // Lấy transaction giảm giá cho khách hàng
                        $transactionDiscountOfCustomer = $this->walletTransactionRepository->query()
                            ->where("foreign_key", $booking->id)
                            ->where('type', WalletTransactionType::SUBTRACT_MONEY_DISCOUNT_SERVICE->value)
                            ->first();
                        if (!$transactionDiscountOfCustomer) {
                            throw new ServiceException(
                                message: __("error.transaction_not_found")
                            );
                        }
                        // Lấy tỷ giá đổi điểm cho dịch vụ di chuyển
                        $exchangeRatePointDiscount = $transactionDiscountOfCustomer->exchange_rate_point;

                        // Trừ tiền giảm giá cho khách hàng
                        $this->walletTransactionRepository->create([
                            'wallet_id' => $clientWallet->id,
                            'type' => WalletTransactionType::REFUND_MONEY_DISCOUNT_SERVICE->value,
                            'point_amount' => $transactionDiscountOfCustomer->point_amount * $exchangeRatePointDiscount,
                            'money_amount' => $transactionDiscountOfCustomer->money_amount,
                            'exchange_rate_point' => $exchangeRatePointDiscount,
                            'status' => WalletTransactionStatus::COMPLETED->value,
                            'transaction_code' => Helper::createDescPayment(PaymentType::REFUND),
                            'foreign_key' => $booking->id,
                            'description' => "Thu hồi tiền giảm giá #{$booking->id} - lý do: {$booking->reason_cancel}",
                            'expired_at' => now(),
                        ]);
                        $clientWallet->balance -= $transactionDiscountOfCustomer->point_amount;
                    }
                }
                $clientWallet->save();

                // Số tiền trả cho kỹ thuật viên
                $amountPayToKtv = max($data['amount_pay_to_ktv'], 0);

                // Nếu Số tiền trả cho kỹ thuật viên lớn hơn 0
                if ($amountPayToKtv > 0) {
                    // Lấy ví kỹ thuật viên
                    $ktvWallet = $this->walletRepository->getWalletByUserId($booking->ktv_user_id);
                    if (!$ktvWallet) {
                        throw new ServiceException(
                            message: __("booking.payment.wallet_technician_not_found")
                        );
                    }
                    $this->walletTransactionRepository->create([
                        'wallet_id' => $ktvWallet->id,
                        'type' => WalletTransactionType::PAYMENT_REFUND_KTV_FOR_BOOKING_CANCEL->value,
                        'point_amount' => $amountPayToKtv * $exchangeRatePoint,
                        'money_amount' => $amountPayToKtv,
                        'exchange_rate_point' => $exchangeRatePoint,
                        'status' => WalletTransactionStatus::COMPLETED->value,
                        'transaction_code' => Helper::createDescPayment(PaymentType::REFUND),
                        'foreign_key' => $booking->id,
                        'description' => "Hoàn tiền booking #{$booking->id}",
                        'expired_at' => now(),
                        'transaction_id' => null,
                    ]);
                    $ktvWallet->balance += $amountPayToKtv;
                    $ktvWallet->save();
                }

                // Gửi thông báo cho khách hàng
                SendNotificationJob::dispatch(
                    userId: $booking->user_id,
                    type: NotificationType::BOOKING_CANCELLED,
                    data: [
                        'booking_id' => $booking->id,
                        'reason' => $booking->reason_cancel,
                    ]
                );

                // Gửi thông báo cho KTV
                SendNotificationJob::dispatch(
                    userId: $booking->ktv_user_id,
                    type: NotificationType::BOOKING_CANCELLED,
                    data: [
                        'booking_id' => $booking->id,
                        'reason' => $booking->reason_cancel,
                    ]
                );
            },
            useTransaction: true,
        );
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







    /**
     *  ---- Protected methods ----
     */

    /**
     * Xử lý thanh toán hoa hồng Affiliate
     * @param $referrerId
     * @param $systemIncome - Số tiền hệ thống thu được sau khi trừ phí
     * @param $bookingId - Id của booking
     * @param $exchangeRate - Tỷ giá đổi tiền
     * @throws ServiceException
     */
    public function processAffiliateCommission(
        $referrerId,
        $systemIncome,
        $bookingId,
        $exchangeRate
    )
    {
        // Lấy thông tin người giới thiệu
        $referrer = $this->userRepository
            ->queryUser()
            ->where('id', $referrerId)
            ->first();
        if (!$referrer) {
            throw new ServiceException(
                message: __("error.user_not_found")
            );
        }

        // Lấy ví của người giới thiệu
        $wallet = $this->walletRepository->getWalletByUserId($referrerId, lockForUpdate: true);
        if (!$wallet) {
            throw new ServiceException(
                message: __("error.wallet_not_found")
            );
        }

        // Lấy cấu hình affiliate dựa trên vai trò của người dùng
        $affiliateConfig = $this->configService->getConfigAffiliate(UserRole::from($referrer->role));
        if ($affiliateConfig->isError()) {
            throw new ServiceException(__("error.config_wallet_error"));
        }
        $affiliateConfigData = $affiliateConfig->getData();

        // Đối với Khách hàng giới thiệu với Khách hàng -> chỉ được hưởng 1 lần
        if ($referrer->role === UserRole::CUSTOMER->value) {
            $checkAffiliate = $this->walletTransactionRepository->query()
                ->where('wallet_id', $wallet->id)
                ->where('type', WalletTransactionType::AFFILIATE->value)
                ->exists();
            if ($checkAffiliate) {
                return;
            }
        }

        // Kiểm tra xem hoa hồng affiliate đã được thanh toán chưa
        $existingCommission = $this->walletTransactionRepository->query()
            ->where('foreign_key', $bookingId)
            ->where('wallet_id', $wallet->id)
            ->where('type', WalletTransactionType::AFFILIATE->value)
            ->exists();

        // Nếu hoa hồng affiliate chưa được thanh toán thì tạo
        if (!$existingCommission) {
            // Tính toán hoa hồng affiliate
            $amount = CalculatePrice::calculatePriceAffiliate(
                price: $systemIncome,
                commissionPercent: $affiliateConfigData['commission_rate'],
                minCommission: $affiliateConfigData['min_commission'],
                maxCommission: $affiliateConfigData['max_commission'],
            );

            // Tạo transaction thanh toán hoa hồng affiliate
            $this->walletTransactionRepository->create([
                'wallet_id' => $wallet->id,
                'foreign_key' => $bookingId,
                'money_amount' => $amount * $exchangeRate,
                'exchange_rate_point' => $exchangeRate,
                'point_amount' => $amount,
                'type' => WalletTransactionType::AFFILIATE->value,
                'status' => WalletTransactionStatus::COMPLETED->value,
                'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                'description' => __('booking.payment.wallet_referred_staff_affiliate'),
                'expired_at' => now(),
                'transaction_id' => null,
                'metadata' => null,
            ]);

            // Cộng số dư ví Người giới thiệu
            $wallet->increment('balance', $amount);
        }
    }

    /**
     * Xử lý hoa hồng của người giới thiệu kỹ thuật viên
     * @param $referrerId - Id của người giới thiệu
     * @param $systemIncome - Số tiền hệ thống thu được sau khi trừ phí
     * @param $bookingId - Id của booking
     * @param $exchangeRate - Tỷ giá đổi tiền
     * @return void
     * @throws ServiceException
     */
    public function processReferralKtvCommission(
        $referrerId,
        $systemIncome,
        $bookingId,
        $exchangeRate
    )
    {
        // Lấy thông tin người giới thiệu
        $referrer = $this->userRepository
            ->queryUser()
            ->whereIn('role', [
                UserRole::AGENCY->value,
                UserRole::KTV->value
            ])
            ->where('id', $referrerId)
            ->first();
        if (!$referrer) {
            throw new ServiceException(
                message: __("error.user_not_found")
            );
        }

        // Lấy ví của người giới thiệu
        $wallet = $this->walletRepository->getWalletByUserId($referrerId, lockForUpdate: true);
        if (!$wallet) {
            throw new ServiceException(
                message: __("error.wallet_not_found")
            );
        }

        // Lấy cấu hình hoa hồng KTV
        switch ($referrer->role) {
            case UserRole::KTV->value:
                // Nếu KTV là leader
                if ($referrer->reviewApplication?->is_leader) {
                    $rateDiscount = (float)$this->configService->getConfigValue(ConfigName::DISCOUNT_RATE_REFERRER_KTV_LEADER);
                } else {
                    $rateDiscount = (float)$this->configService->getConfigValue(ConfigName::DISCOUNT_RATE_REFERRER_KTV);
                }
                break;
            case UserRole::AGENCY->value:
                $rateDiscount = (float)$this->configService->getConfigValue(ConfigName::DISCOUNT_RATE_REFERRER_AGENCY);
                break;
            default:
                // Dự phòng cho trường hợp có role mới
                return;
        }


        // Kiểm tra xem hoa hồng KTV đã được thanh toán chưa
        $existingCommission = $this->walletTransactionRepository->query()
            ->where('foreign_key', $bookingId)
            ->where('wallet_id', $wallet->id)
            ->where('type', WalletTransactionType::REFERRAL_KTV->value)
            ->exists();

        if (!$existingCommission && $rateDiscount > 0) {
            // Tính giá tiền hoa hồng cho người giới thiệu
            $amount = CalculatePrice::calculatePriceReferrer($systemIncome, $rateDiscount);

            // Tạo transaction thanh toán hoa hồng KTV
            $this->walletTransactionRepository->create([
                'wallet_id' => $wallet->id,
                'foreign_key' => $bookingId,
                'money_amount' => $amount * $exchangeRate,
                'exchange_rate_point' => $exchangeRate,
                'point_amount' => $amount,
                'type' => WalletTransactionType::REFERRAL_KTV->value,
                'status' => WalletTransactionStatus::COMPLETED->value,
                'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                'description' => __('booking.payment.wallet_referred_staff_ktv'),
                'expired_at' => now(),
                'transaction_id' => null,
                'metadata' => null,
            ]);

            // Cộng số dư ví Người giới thiệu
            $wallet->increment('balance', $amount);
        }
    }
}
