<?php

namespace App\Services;

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
use App\Models\Config;
use App\Repositories\BookingRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use App\Services\ConfigService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WalletService extends BaseService
{
    public function __construct(
        protected WalletRepository $walletRepository,
        protected WalletTransactionRepository $walletTransactionRepository,
        protected BookingRepository $bookingRepository,
        protected ConfigService $configService,
    ) {
        parent::__construct();
    }

    /**
     * Khởi tạo thanh toán booking, tạo giao dịch thanh toán và cập nhật số dư ví khách hàng.
     * @param int $bookingId
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function paymentInitBooking(
        int $bookingId,
    ): ServiceReturn {
        DB::beginTransaction();
        try {
            // Lấy tỉ lệ đổi tiền từ config
            $exchangeRate = (float) $this->configService->getConfigValue(ConfigName::CURRENCY_EXCHANGE_RATE);

            // Tìm booking theo id
            $booking = $this->bookingRepository->query()->find($bookingId);
            if (!$booking) {
                throw new ServiceException(
                    message: __("booking.payment.booking_not_found")
                );
            }

            // Kiểm tra số dư ví khách hàng có đủ không
            $walletCustomerCheck = $this->checkUserWalletBalance(
                userId: $booking->user_id,
                price: $booking->price,
                lockForUpdate: true,
            );
            // Nếu số dư ví khách hàng không đủ
            if (!$walletCustomerCheck['is_enough']) {
                throw new ServiceException(
                    message: __("booking.payment.wallet_customer_not_enough")
                );
            }

            // Kiểm tra số dư ví kỹ thuật viên có đủ không
            $walletKtvCheck = $this->checkKtvWalletBalance(
                ktvId: $booking->ktv_user_id,
                price: $booking->price,
                lockForUpdate: true,
            );
            // Nếu số dư ví kỹ thuật viên không đủ
            if (!$walletKtvCheck['is_enough']) {
                throw new ServiceException(
                    message: __("booking.payment.wallet_technician_not_enough")
                );
            }
            // Lấy ví khách hàng
            $walletCustomer = $walletCustomerCheck['wallet'];
            // Lấy ví kỹ thuật viên
            $walletKtv = $walletKtvCheck['wallet'];
            // Lấy tỉ lệ chiết khấu cho kỹ thuật viên
            $rate = $walletKtvCheck['rate'];

            // tiến hành lưu giao dịch cho khách hàng
            $this->walletTransactionRepository->create([
                'wallet_id' => $walletCustomer->id,
                'foreign_key' => $bookingId,
                'money_amount' => $booking->price * $exchangeRate,
                'exchange_rate_point' => $exchangeRate,
                'point_amount' => -$booking->price,
                'balance_after' => $walletCustomer->balance - $booking->price,
                'type' => WalletTransactionType::PAYMENT->value,
                'status' => WalletTransactionStatus::COMPLETED->value,
                'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                'description' => __('booking.payment.wallet_customer'),
                'expired_at' => now(),
                'transaction_id' => null,
                'metadata' => null,
            ]);
            // Trừ số dư ví Khách hàng
            $walletCustomer->balance -= $booking->price;
            $walletCustomer->save();

            // Tính số tiền mà kỹ thuật viên dc hưởng (trừ chiết khấu)
            $ktvAmount = Helper::calculatePriceDiscountForKTV($booking->price, $rate);
            // tạo transaction cho kỹ thuật viên
            $this->walletTransactionRepository->create([
                'wallet_id' => $walletKtv->id,
                'foreign_key' => $bookingId,
                'money_amount' => $ktvAmount * $exchangeRate,
                'exchange_rate_point' => $exchangeRate,
                'point_amount' => $ktvAmount,
                'balance_after' => $walletKtv->balance + $ktvAmount,
                'type' => WalletTransactionType::PAYMENT_FOR_KTV->value,
                'status' => WalletTransactionStatus::COMPLETED->value,
                'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                'description' => __('booking.payment.wallet_technician'),
                'expired_at' => null,
                'metadata' => null,
                'transaction_id' => null,
            ]);
            // Cộng số dư ví Kỹ thuật viên
            $walletKtv->balance += $ktvAmount;
            $walletKtv->save();

            // Cập nhật trạng thái đặt lịch thành xác nhận
            $booking->status = BookingStatus::CONFIRMED->value;
            $booking->save();
            DB::commit();

            // Thông báo thay đổi số dư ví cho khách hàng
            SendNotificationJob::dispatch(
                userId: $booking->user_id,
                type: NotificationType::PAYMENT_COMPLETE,
                data: [
                    'booking_id' => $booking->id,
                ]
            );
            // Thông báo thay đổi số dư ví cho kỹ thuật viên
            SendNotificationJob::dispatch(
                userId: $booking->ktv_user_id,
                type: NotificationType::PAYMENT_SERVICE_FOR_TECHNICIAN,
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
                message: "Lỗi WalletService@paymentBooking",
                ex: $exception
            );
            throw $exception;
        }
    }

    /**
     * Hoàn lại tiền cho khách hàng khi hủy booking
     * @param string $bookingId
     * @param string|null $reason
     * @return ServiceReturn
     */
    public function refundCancelBooking(string $bookingId, ?string $reason = null): ServiceReturn
    {
        DB::beginTransaction();
        try {
            $booking = $this->bookingRepository->query()->find($bookingId);
            if (!$booking) {
                throw new ServiceException(
                    message: __("booking.not_found")
                );
            }
            // Kiểm tra booking có đang hủy không
            if ($booking->status != BookingStatus::CANCELED->value) {
                throw new ServiceException(
                    message: __("booking.not_canceled")
                );
            }

            /**
             * Đối với Khách hàng
             */
            // Lấy tỉ giá
            // Lấy transaction gốc để tham khảo (chỉ đọc, không sửa)
            $transactionOfCustomer = $this->walletTransactionRepository->query()
                ->where("foreign_key", $booking->id)
                ->where('type', WalletTransactionType::PAYMENT->value)
                ->first();
            // Nếu chưa có transaction PAYMENT (cancel ngay sau khi tạo booking)
            // thì không cần refund, chỉ cần return success
            if (!$transactionOfCustomer) {
                throw new ServiceException(
                    message: __("booking.not_paid")
                );
            }

            // Kiểm tra đã refund chưa
            $existingRefund = $this->walletTransactionRepository->query()
                ->where('foreign_key', $booking->id)
                ->where('type', WalletTransactionType::REFUND->value)
                ->exists();
            if ($existingRefund) {
                throw new ServiceException(
                    message: __("booking.refunded")
                );
            }

            // Lấy wallet customer với lock
            $walletCustomer = $this->walletRepository->query()
                ->where('user_id', $booking->user_id)
                ->lockForUpdate()
                ->first();

            if (!$walletCustomer) {
                throw new ServiceException(
                    message: __("error.not_found_wallet")
                );
            }
            // Tạo transaction REFUND mới
            $this->walletTransactionRepository->create([
                'wallet_id' => $walletCustomer->id,
                'type' => WalletTransactionType::REFUND->value,
                'point_amount' => $transactionOfCustomer->point_amount,
                'balance_after' => $walletCustomer->balance + $transactionOfCustomer->point_amount,
                'money_amount' => $transactionOfCustomer->money_amount,
                'exchange_rate_point' => $transactionOfCustomer->exchange_rate_point,
                'status' => WalletTransactionStatus::COMPLETED->value,
                'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                'foreign_key' => $booking->id,
                'description' => "Hoàn tiền booking #{$booking->id} - lý do: {$reason}",
                'expired_at' => now(),
                'transaction_id' => null,
            ]);
            // Cập nhật balance
            $walletCustomer->balance += $booking->price;
            $walletCustomer->save();


            /**
             * Đối với KTV
             */
            // Lấy transaction gốc để tham khảo (chỉ đọc, không sửa)
            $transactionOfKtv = $this->walletTransactionRepository->query()
                ->where("foreign_key", $booking->id)
                ->where('type', WalletTransactionType::PAYMENT_FOR_KTV->value)
                ->first();
            // Nếu chưa có transaction PAYMENT_FOR_KTV (cancel ngay sau khi tạo booking)
            // thì không cần refund, chỉ cần return success
            if (!$transactionOfKtv) {
                throw new ServiceException(
                    message: __("booking.not_paid_for_ktv")
                );
            }
            // Kiểm tra đã thu hồi tiền thanh toán cho KTV khi hủy booking chưa
            $existingRefundKtv = $this->walletTransactionRepository->query()
                ->where('foreign_key', $booking->id)
                ->where('type', WalletTransactionType::RETRIEVE_PAYMENT_REFUND_KTV->value)
                ->exists();
            if ($existingRefundKtv) {
                throw new ServiceException(
                    message: __("booking.refunded_for_ktv")
                );
            }
            // Lấy wallet ktv với lock
            $walletKtv = $this->walletRepository->query()
                ->where('user_id', $booking->technician_id)
                ->lockForUpdate()
                ->first();
            if (!$walletKtv) {
                throw new ServiceException(
                    message: __("error.not_found_wallet")
                );
            }
            // Tạo transaction thu hồi tiền thanh toán cho KTV khi hủy booking
            $this->walletTransactionRepository->create([
                'wallet_id' => $walletKtv->id,
                'type' => WalletTransactionType::RETRIEVE_PAYMENT_REFUND_KTV->value,
                'point_amount' => $transactionOfKtv->price,
                'amount' => $transactionOfKtv->point_amount,
                'balance_after' => $walletKtv->balance - $transactionOfKtv->point_amount,
                'money_amount' => $transactionOfKtv->money_amount,
                'exchange_rate_point' => $transactionOfKtv->exchange_rate_point,
                'status' => WalletTransactionStatus::COMPLETED->value,
                'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                'foreign_key' => $booking->id,
                'description' => "Thu hồi tiền thanh toán cho KTV khi hủy booking #{$booking->id} - lý do: {$reason}",
                'expired_at' => now(),
                'transaction_id' => null,
            ]);
            // Cập nhật balance
            $walletKtv->balance -= $transactionOfKtv->point_amount;
            $walletKtv->save();

            // Gửi thông báo
            SendNotificationJob::dispatch(
                userId: $booking->user_id,
                type: NotificationType::BOOKING_REFUNDED,
                data: [
                    'booking_id' => $booking->id,
                    'amount' => $booking->price,
                    'reason' => $reason,
                ]
            );
            SendNotificationJob::dispatch(
                userId: $booking->ktv_user_id,
                type: NotificationType::BOOKING_REFUNDED,
                data: [
                    'booking_id' => $booking->id,
                    'amount' => $booking->price,
                    'reason' => $reason,
                ]
            );
            DB::commit();
            return ServiceReturn::success(
                message: __("booking.refunded")
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi ServiceService@refundCancelBooking",
                ex: $exception
            );
            throw $exception;
        }
    }

    /**
     * Khởi tạo ví cho nhân viên
     * @param int $staffId
     * @return ServiceReturn
     */
    public function initWalletForStaff(int $staffId): ServiceReturn
    {
        try {
            $wallet = $this->walletRepository->query()->where('user_id', $staffId)->first();
            if (!$wallet) {
                $this->walletRepository->create([
                    'user_id' => $staffId,
                    'is_active' => true,
                    'balance' => 0,
                ]);
            }
            return ServiceReturn::success(
                message: __("wallet.init_success")
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi WalletService@initWalletForStaff",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("wallet.init_failed")
            );
        }
    }

    /**
     * Thanh toán phí chiết khấu cho người giới thiệu Affiliate
     * @param int $amount Số tiền hoa hồng
     * @param int $userId ID của người giới thiệu
     * @param string $bookingId ID của booking
     * @return ServiceReturn
     */
    public function paymentCommissionFeeForReferralAffiliate($amount, int $userId, $bookingId): ServiceReturn
    {
        try {
            $wallet = $this->walletRepository->query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();
            if (!$wallet) {
                throw new ServiceException(
                    message: __("wallet.not_found")
                );
            }

            // Check if commission already paid (Idempotency)
            $existingCommission = $this->walletTransactionRepository->query()
                ->where('foreign_key', $bookingId)
                ->where('wallet_id', $wallet->id)
                ->where('type', WalletTransactionType::AFFILIATE->value)
                ->exists();

            if ($existingCommission) {
                return ServiceReturn::success(
                    message: __("booking.pay_commission_fee_success")
                );
            }


            $exchangeRate = (int)$this->configService->getConfigValue(ConfigName::CURRENCY_EXCHANGE_RATE);

            $this->walletTransactionRepository->create([
                'wallet_id' => $wallet->id,
                'foreign_key' => $bookingId,
                'money_amount' => $amount * $exchangeRate,
                'exchange_rate_point' => $exchangeRate,
                'point_amount' => $amount,
                'balance_after' => $wallet->balance + $amount,
                'type' => WalletTransactionType::AFFILIATE->value,
                'status' => WalletTransactionStatus::COMPLETED->value,
                'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                'description' => __('booking.payment.wallet_referred_staff_affiliate'),
                'expired_at' => now(),
                'transaction_id' => null,
                'metadata' => null,
            ]);

            $wallet->balance += $amount;
            $wallet->save();
            return ServiceReturn::success(
                message: __("booking.pay_commission_fee_success")
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi WalletService@paymentCommissionFeeForReferralAffiliate",
                ex: $exception
            );
            throw $exception;
        }
    }


    /**
     * Thanh toán phí chiết khấu cho người giới thiệu
     * @param int $amount Số tiền hoa hồng
     * @param int $userId ID của người giới thiệu
     * @param string $bookingId ID của booking
     * @return ServiceReturn
     * @throws ServiceException
     */
    public function paymentCommissionFeeForReferral($amount, int $userId, $bookingId): ServiceReturn
    {
        try {
            $wallet = $this->walletRepository->query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();
            if (!$wallet) {
                throw new ServiceException(
                    message: __("wallet.not_found")
                );
            }

            // Check if commission already paid (Idempotency)
            $existingCommission = $this->walletTransactionRepository->query()
                ->where('foreign_key', $bookingId)
                ->where('wallet_id', $wallet->id)
                ->where('type', WalletTransactionType::REFERRAL_KTV->value)
                ->exists();

            if ($existingCommission) {
                return ServiceReturn::success(
                    message: __("booking.pay_commission_fee_success")
                );
            }

            $exchangeRate = (int)$this->configService->getConfigValue(ConfigName::CURRENCY_EXCHANGE_RATE);

            $this->walletTransactionRepository->create([
                'wallet_id' => $wallet->id,
                'foreign_key' => $bookingId,
                'money_amount' => $amount * $exchangeRate,
                'exchange_rate_point' => $exchangeRate,
                'point_amount' => $amount,
                'balance_after' => $wallet->balance + $amount,
                'type' => WalletTransactionType::REFERRAL_KTV->value,
                'status' => WalletTransactionStatus::COMPLETED->value,
                'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                'description' => __('booking.payment.wallet_referred_staff_ktv'),
                'expired_at' => now(),
                'transaction_id' => null,
                'metadata' => null,
            ]);

            $wallet->balance += $amount;
            $wallet->save();
            return ServiceReturn::success(
                message: __("booking.pay_commission_fee_success")
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi WalletService@paymentCommissionFeeForReferral",
                ex: $exception
            );
            throw $exception;
        }
    }

    /**
     * Kiểm tra số dư ví của người dùng có đủ không
     * @param $userId
     * @param $price
     * @param bool $lockForUpdate
     * @return array {
     *     'balance' => int, // Số dư ví của người dùng
     *     'is_enough' => bool, // Có đủ số dư không
     *     'wallet' => Wallet, // Ví của người dùng
     * }
     * @throws ServiceException
     */
    public function checkUserWalletBalance($userId, $price, bool $lockForUpdate = false)
    {
        $walletCustomer = $this->walletRepository->query()
            ->where('user_id', $userId);
        if ($lockForUpdate) {
            $walletCustomer->lockForUpdate();
        }
        $walletCustomer = $walletCustomer->first();
        if (!$walletCustomer || !$walletCustomer->is_active) {
            throw new ServiceException(
                message: __("booking.payment.wallet_customer_not_found")
            );
        }
        return [
            'balance' => $walletCustomer->balance,
            'wallet' => $walletCustomer,
            'is_enough' => $walletCustomer->balance >= $price,
        ];
    }

    /**
     * Kiểm tra số dư ví của kỹ thuật viên có đủ không
     * @param $ktvId
     * @param $price
     * @param bool $lockForUpdate
     * @return array {
     *     'balance' => int, // Số dư ví của kỹ thuật viên
     *     'is_enough' => bool, // Có đủ số dư không
     *     'wallet' => Wallet, // Ví của kỹ thuật viên
     *     'discount' => float, // Số tiền chiết khấu của kỹ thuật viên
     * }
     * @throws ServiceException
     */
    public function checkKtvWalletBalance($ktvId, $price, bool $lockForUpdate = false)
    {
        // lấy ví của ktv
        $ktvWallet = $this->walletRepository->query()
            ->where('user_id', $ktvId);
        if ($lockForUpdate) {
            $ktvWallet->lockForUpdate();
        }
        $ktvWallet = $ktvWallet->first();

        if (!$ktvWallet || !$ktvWallet->is_active) {
            throw new ServiceException(
                message: __("booking.payment.tech_not_active")
            );
        }
        $balanceKtv = $ktvWallet->balance;

        // lấy mức chiết khấu của nhà cung cấp
        $rateDiscount = $this->configService->getConfigValue(ConfigName::DISCOUNT_RATE);
        $rate = floatval($rateDiscount);

        // Tính số tiền Hệ thống thu (Commission)
        $systemMinus = Helper::calculateSystemMinus($price, $rate);

        return [
            'balance' => $balanceKtv,
            'wallet' => $ktvWallet,
            'is_enough' => $balanceKtv >= $systemMinus,
            'system_minus' => $systemMinus,
            'rate' => $rate,
        ];
    }

}
