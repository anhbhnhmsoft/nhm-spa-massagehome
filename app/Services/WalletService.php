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
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Jobs\SendNotificationJob;
use App\Models\User;
use App\Models\Wallet;

use App\Repositories\BookingRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use Illuminate\Support\Facades\DB;

class WalletService extends BaseService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected WalletRepository $walletRepository,
        protected WalletTransactionRepository $walletTransactionRepository,
        protected BookingRepository $bookingRepository,
        protected ConfigService $configService,
    ) {
        parent::__construct();
    }

    /**
     * Tạo transaction thanh toán booking cho Khách hàng
     * @param Wallet $walletCustomer - Ví của khách hàng
     * @param $bookingId - Id của booking
     * @param $price - Giá booking
     * @param $exchangeRate - Tỷ giá đổi tiền
     */
    public function createPaymentServiceBookingForCustomer(
        Wallet $walletCustomer,
        $bookingId,
        $price,
        $exchangeRate,
    )
    {
        $balanceAfter = $walletCustomer->balance - $price;

        // Tạo transaction thanh toán booking cho Khách hàng
        $this->walletTransactionRepository->create([
            'wallet_id' => $walletCustomer->id,
            'foreign_key' => $bookingId,
            'money_amount' => $price * $exchangeRate,
            'exchange_rate_point' => $exchangeRate,
            'point_amount' => $price,
            'balance_after' => $balanceAfter,
            'type' => WalletTransactionType::PAYMENT->value,
            'status' => WalletTransactionStatus::COMPLETED->value,
            'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
            'description' => __('booking.payment.wallet_customer'),
            'expired_at' => now(),
            'transaction_id' => null,
            'metadata' => null,
        ]);

        // Trừ số dư ví Khách hàng
        $walletCustomer->balance -= $price;
        $walletCustomer->save();
    }

    /**
     * Tạo transaction thanh toán booking cho KTV
     * @param Wallet $walletKtv - Ví của kỹ thuật viên
     * @param $bookingId - Id của booking
     * @param $price - Giá booking
     * @param $exchangeRate - Tỷ giá đổi tiền
     * @param $discountRate - Chiết khấu
     */
    public function createPaymentServiceBookingForKtv(
        Wallet $walletKtv,
        $bookingId,
        $price,
        $exchangeRate,
        $discountRate
    )
    {
        //Tính số tiền mà kỹ thuật viên dc hưởng(trừ chiết khấu)
        $ktvAmount = Helper::calculatePriceDiscountForKTV($price, $discountRate);

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
    }
    /**
     * Lấy ví của người dùng
     * @param $userId
     * @param bool $lockForUpdate
     * @return Wallet|null
     */
    public function getWalletByUserId($userId, bool $lockForUpdate = false)
    {
        $query = $this->walletRepository->query()
            ->where('is_active', true)
            ->where('user_id', $userId);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }
        return $query->first();
    }

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
            ->find($referrerId);
        if (!$referrer) {
            throw new ServiceException(
                message: __("error.user_not_found")
            );
        }

        // Lấy ví của người giới thiệu
        $wallet = $this->getWalletByUserId($referrerId);
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
            $amount = Helper::calculatePriceAffiliate(
                price: $systemIncome,
                commissionPercent: $affiliateConfigData['commission_percent'],
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
                'balance_after' => $wallet->balance + $amount,
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
            ->find($referrerId);
        if (!$referrer) {
            throw new ServiceException(
                message: __("error.user_not_found")
            );
        }

        // Lấy ví của người giới thiệu
        $wallet = $this->getWalletByUserId($referrerId);
        if (!$wallet) {
            throw new ServiceException(
                message: __("error.wallet_not_found")
            );
        }

        // Lấy cấu hình hoa hồng KTV
        switch ($referrer->role) {
            case UserRole::KTV->value:
                // Nếu KTV
                if ($referrer->reviewApplications?->is_leader){
                    $rateDiscount = (float) $this->configService->getConfigValue(ConfigName::DISCOUNT_RATE_REFERRER_KTV_LEADER);
                }else{
                    $rateDiscount = (float) $this->configService->getConfigValue(ConfigName::DISCOUNT_RATE_REFERRER_KTV);
                }
                break;
            case UserRole::AGENCY->value:
                $rateDiscount = (float) $this->configService->getConfigValue(ConfigName::DISCOUNT_RATE_REFERRER_AGENCY);
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

        if (!$existingCommission) {
            // Tính giá tiền hoa hồng cho người giới thiệu
            $amount = Helper::calculatePriceReferrer($systemIncome, $rateDiscount);

            // Tạo transaction thanh toán hoa hồng KTV
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

            // Cộng số dư ví Người giới thiệu
            $wallet->increment('balance', $amount);
        }
    }

    /**
     *
     * Xử lý hoa hồng thưởng của người giới thiệu kỹ thuật viên
     * @param $referrerId
     * @param $userId
     * @param $rewardAmount
     * @param $exchangeRate
     * @return void
     * @throws ServiceException
     */
    public function processRewardReferralKtvCommission(
        $referrerId,
        $userId,
        $rewardAmount,
        $exchangeRate
    )
    {
        // Lấy ví người giới thiệu
        $walletRef = $this->walletRepository->queryWallet()
            ->whereHas('user', function ($query) use ($referrerId) {
                $query->where('id', $referrerId);
                $query->whereIn('role', [UserRole::AGENCY->value, UserRole::KTV->value]);
                $query->where('is_active', true);
            })
            ->lockForUpdate()
            ->first();

        if (!$walletRef) {
            throw new ServiceException(
                message: __("error.wallet_not_found")
            );
        }

        // Kiểm tra xem hoa hồng KTV đã được thanh toán chưa
        $existingReward = $this->walletTransactionRepository->query()
            ->where('foreign_key', $userId)
            ->where('wallet_id', $walletRef->id)
            ->where('type', WalletTransactionType::REFERRAL_INVITE_KTV_REWARD->value)
            ->exists();

        // Nếu chưa thanh toán hoa hồng KTV thì tạo transaction và cộng số dư ví Người giới thiệu
        if (!$existingReward) {
            $this->walletTransactionRepository->create([
                'wallet_id' => $walletRef->id,
                'foreign_key' => (string) $userId,
                'money_amount' => $rewardAmount * $exchangeRate,
                'exchange_rate_point' => $exchangeRate,
                'point_amount' => $rewardAmount,
                'balance_after' => $walletRef->balance + $rewardAmount,
                'type' => WalletTransactionType::REFERRAL_INVITE_KTV_REWARD->value,
                'status' => WalletTransactionStatus::COMPLETED->value,
                'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                'description' => __('wallet.referral_ktv_reward'),
                'expired_at' => now(),
                'transaction_id' => null,
                'metadata' => null,
            ]);

            // Cộng số dư ví Người giới thiệu
            $walletRef->increment('balance', $rewardAmount);
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
                ->where('user_id', $booking->ktv_user_id)
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
                'point_amount' => $transactionOfKtv->point_amount,
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
        $walletCustomer = $this->getWalletByUserId($userId, $lockForUpdate);
        if (!$walletCustomer) {
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

}
