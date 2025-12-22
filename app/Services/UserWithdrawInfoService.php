<?php

namespace App\Services;

use App\Core\Helper;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\ConfigName;
use App\Enums\PaymentType;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Repositories\UserWithdrawInfoRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use App\Services\ConfigService;
use Illuminate\Support\Facades\DB;

class UserWithdrawInfoService extends BaseService
{
    public function __construct(
        protected UserWithdrawInfoRepository $userWithdrawInfoRepository,
        protected WalletRepository $walletRepository,
        protected WalletTransactionRepository $walletTransactionRepository,
        protected ConfigService $configService,
    ) {
        parent::__construct();
    }

    /**
     * Lấy danh sách withdraw info của user
     */
    public function getWithdrawInfoByUserId(int $userId): ServiceReturn
    {
        try {
            $withdrawInfo = $this->userWithdrawInfoRepository->query()
                ->where('user_id', $userId)
                ->get();
            if (!$withdrawInfo || $withdrawInfo->isEmpty()) {
                throw new ServiceException(
                    message: __("error.withdraw_info_not_found")
                );
            }
            return ServiceReturn::success(
                data: $withdrawInfo,
            );
        } catch (ServiceException $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi UserWithdrawInfoService@getWithdrawInfoByUserId",
                ex: $exception
            );
            return ServiceReturn::error(message: __("common_error.server_error"));
        }
    }

    /**
     * Tạo thông tin rút tiền
     */
    public function createWithdrawInfo(int $userId, int $type, array $config): ServiceReturn
    {
        try {
            $withdrawInfo = $this->userWithdrawInfoRepository->create([
                'user_id' => $userId,
                'type' => $type,
                'config' => $config,
            ]);

            return ServiceReturn::success(data: $withdrawInfo, message: __("common.success.data_created"));
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi UserWithdrawInfoService@createWithdrawInfo",
                ex: $exception
            );
            return ServiceReturn::error(message: __("common_error.server_error"));
        }
    }

    /**
     * Tạo yêu cầu rút tiền (ghi transaction pending)
     */
    public function requestWithdraw(int $userId, int $withdrawInfoId, float $amount, ?string $note = null): ServiceReturn
    {
        DB::beginTransaction();
        try {
            // Kiểm tra withdraw info thuộc user
            $withdrawInfo = $this->userWithdrawInfoRepository->query()
                ->where('id', $withdrawInfoId)
                ->where('user_id', $userId)
                ->first();
            if (!$withdrawInfo) {
                throw new ServiceException(message: __("error.withdraw_info_not_found"));
            }

            // Lấy wallet
            $wallet = $this->walletRepository->queryWallet()
                ->where('user_id', $userId)
                ->first();
            if (!$wallet) {
                throw new ServiceException(message: __("wallet.not_found"));
            }

            if ($wallet->balance < $amount) {
                throw new ServiceException(message: __("wallet.not_enough_balance"));
            }

            // Trừ tiền ngay khi tạo lệnh rút
            $wallet->balance = $wallet->balance - $amount;
            $wallet->save();

            // Tỷ giá (point -> money)
            $exchangeRateConfig = $this->configService->getConfig(ConfigName::CURRENCY_EXCHANGE_RATE);
            $exchangeRate = $exchangeRateConfig->getData()['config_value'] ?? 1;

            $meta = $withdrawInfo->config ?? [];
            if (!is_array($meta)) {
                $decoded = json_decode((string) $meta, true);
                $meta = is_array($decoded) ? $decoded : [];
            }

            // Tạo transaction pending
            $transaction = $this->walletTransactionRepository->create([
                'wallet_id' => $wallet->id,
                'foreign_key' => $withdrawInfo->id,
                'money_amount' => $amount * $exchangeRate,
                'exchange_rate_point' => $exchangeRate,
                'point_amount' => $amount,
                'balance_after' => $wallet->balance,
                'type' => WalletTransactionType::WITHDRAWAL->value,
                'status' => WalletTransactionStatus::PENDING->value,
                'transaction_code' => Helper::createDescPayment(PaymentType::WITHDRAWAL),
                'description' => $note,
                'metadata' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                'expired_at' => now()->addDays(7),
            ]);

            DB::commit();

            return ServiceReturn::success(
                data: [
                    'id' => (string)$transaction->id,
                    'wallet_id' => (string)$wallet->id,
                    'foreign_key' => (string)$withdrawInfo->id,
                    'money_amount' => $transaction->money_amount,
                    'exchange_rate_point' => $transaction->exchange_rate_point,
                    'point_amount' => $transaction->point_amount,
                    'balance_after' => $transaction->balance_after,
                    'type' => $transaction->type,
                    'status' => $transaction->status,
                    'transaction_code' => $transaction->transaction_code,
                    'description' => $transaction->description,
                    'metadata' => $meta,
                    'expired_at' => $transaction->expired_at,
                ],
                message: __("wallet.withdraw_request_pending")
            );
        } catch (ServiceException $exception) {
            DB::rollBack();
            return ServiceReturn::error(message: $exception->getMessage());
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi UserWithdrawInfoService@requestWithdraw",
                ex: $exception
            );
            return ServiceReturn::error(message: __("common_error.server_error"));
        }
    }
}
