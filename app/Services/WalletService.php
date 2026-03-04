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

use App\Models\WalletTransaction;
use App\Repositories\BookingRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use Illuminate\Database\Eloquent\Model;
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

    public function getTransactionRepository(): WalletTransactionRepository
    {
        return $this->walletTransactionRepository;
    }

    /**
     * Tạo transaction rút tiền
     * @param Wallet $walletCustomer
     * @param $withdrawInfoId - Id của thông tin rút tiền
     * @param $withdrawMoney - Số tiền rút (tiền đã trừ phí rút)
     * @param $exchangeRate - Tỷ giá đổi tiền
     * @param null $note - Ghi chú
     * @return Model
     */
    public function createWithdraw(
        Wallet $walletCustomer,
        $withdrawInfoId,
        $withdrawMoney,
        $exchangeRate,
        $note = null,
    )
    {
        // Tạo transaction pending
        $transaction = $this->walletTransactionRepository->create([
            'wallet_id' => $walletCustomer->id,
            'foreign_key' => $withdrawInfoId,
            'money_amount' => $withdrawMoney * $exchangeRate,
            'exchange_rate_point' => $exchangeRate,
            'point_amount' => $withdrawMoney,
            'type' => WalletTransactionType::WITHDRAWAL->value,
            'status' => WalletTransactionStatus::PENDING->value,
            'transaction_code' => Helper::createDescPayment(PaymentType::WITHDRAWAL),
            'description' => $note ?? null,
            'metadata' => null,
            'expired_at' => now()->addDays(7),
        ]);

        // Trừ số dư khả dụng
        $walletCustomer->decrement('balance', $withdrawMoney);

        // Cộng vào số dư đóng băng
        $walletCustomer->increment('frozen_balance', $withdrawMoney);

        return $transaction;
    }

    /**
     * Tạo transaction phí rút tiền
     * @param Wallet $walletCustomer
     * @param $transactionId
     * @param $feeAmount
     * @param $exchangeRate
     * @return void
     */
    public function createWithdrawFee(
        Wallet $walletCustomer,
        $transactionId,
        $feeAmount,
        $exchangeRate,
    )
    {
        // Tạo transaction phí rút
        $this->walletTransactionRepository->create([
            'wallet_id' => $walletCustomer->id,
            'foreign_key' => $transactionId,
            'money_amount' => $feeAmount * $exchangeRate,
            'exchange_rate_point' => $exchangeRate,
            'point_amount' => $feeAmount,
            'type' => WalletTransactionType::FEE_WITHDRAW->value,
            'status' => WalletTransactionStatus::PENDING->value, // Chờ xử lý
            'transaction_code' => Helper::createDescPayment(PaymentType::WITHDRAWAL),
            'description' => null,
            'metadata' => null,
            'expired_at' => null,
        ]);

        // Trừ số dư khả dụng
        $walletCustomer->decrement('balance', $feeAmount);
        // Cộng vào số dư đóng băng
        $walletCustomer->increment('frozen_balance', $feeAmount);
    }

    /**
     * Xác nhận rút tiền
     * @param WalletTransaction $transactionWithdraw
     * @param WalletTransaction $transactionWithdrawFee
     * @param Wallet $wallet
     * @return void
     */
    public function confirmWithdraw(
        WalletTransaction $transactionWithdraw,
        WalletTransaction $transactionWithdrawFee,
        Wallet $wallet,
    )
    {
        // Cập nhật transaction thành công
        $transactionWithdraw->update([
            'status' => WalletTransactionStatus::COMPLETED->value,
        ]);

        // Cập nhật transaction phí rút thành công
        $transactionWithdrawFee->update([
            'status' => WalletTransactionStatus::COMPLETED->value,
        ]);

        // Trừ số tiền phí rút từ số dư đóng băng
        $wallet->decrement('frozen_balance', $transactionWithdraw->point_amount);
        // Trừ luôn phí rút tiền từ số dư đóng băng
        $wallet->decrement('frozen_balance', $transactionWithdrawFee->point_amount);
    }

    public function cancelWithdraw(
        WalletTransaction $transactionWithdraw,
        WalletTransaction $transactionWithdrawFee,
        Wallet $wallet,
    )
    {
        // Cập nhật transaction thành công
        $transactionWithdraw->update([
            'status' => WalletTransactionStatus::CANCELLED->value,
        ]);

        // Cập nhật transaction phí rút thành công
        $transactionWithdrawFee->update([
            'status' => WalletTransactionStatus::CANCELLED->value,
        ]);

        // Trừ số tiền phí rút từ số dư đóng băng
        $wallet->decrement('frozen_balance', $transactionWithdraw->point_amount);
        // Trừ luôn phí rút tiền từ số dư đóng băng
        $wallet->decrement('frozen_balance', $transactionWithdrawFee->point_amount);

        // Cộng lại số tiền phí rút vào số dư
        $wallet->increment('balance', $transactionWithdraw->point_amount);
        // Cộng lại luôn phí rút tiền vào số dư
        $wallet->increment('balance', $transactionWithdrawFee->point_amount);
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
}
