<?php

namespace App\Services;

use App\Core\Helper;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\ConfigName;
use App\Enums\Jobs\WalletTransCase;
use App\Enums\PaymentType;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Jobs\WalletTransactionJob;
use App\Repositories\UserWithdrawInfoRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use App\Services\ConfigService;
use Illuminate\Support\Facades\DB;

class UserWithdrawInfoService extends BaseService
{
    public function __construct(
        protected UserWithdrawInfoRepository  $userWithdrawInfoRepository,
        protected WalletRepository            $walletRepository,
        protected WalletTransactionRepository $walletTransactionRepository,
        protected ConfigService               $configService,
    )
    {
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
            return ServiceReturn::success(
                data: $withdrawInfo,
            );
        }
        catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi UserWithdrawInfoService@getWithdrawInfoByUserId",
                ex: $exception
            );
            return ServiceReturn::error(message: __("common_error.server_error"));
        }
    }

    /**
     * Lấy thông tin withdraw info của user theo id
     * @param int $userId
     * @param int $withdrawInfoId
     * @return ServiceReturn
     */
    public function getDetailWithdrawInfoByUserId(int $userId, int $withdrawInfoId): ServiceReturn
    {
        try {
            $withdrawInfo = $this->userWithdrawInfoRepository->query()
                ->where('user_id', $userId)
                ->where('id', $withdrawInfoId)
                ->first();
            if (!$withdrawInfo) {
                throw new ServiceException(message: __("error.withdraw_info_not_found"));
            }
            return ServiceReturn::success(
                data: $withdrawInfo,
            );
        }
        catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi UserWithdrawInfoService@getDetailWithdrawInfoByUserId",
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
     * Xóa thông tin rút tiền
     */
    public function deleteWithdrawInfo(int $userId, int $withdrawInfoId): ServiceReturn
    {
        try {
            $withdrawInfo = $this->userWithdrawInfoRepository->query()
                ->where('user_id', $userId)
                ->where('id', $withdrawInfoId)
                ->first();
            if (!$withdrawInfo) {
                throw new ServiceException(message: __("error.withdraw_info_not_found"));
            }
            $withdrawInfo->delete();
            return ServiceReturn::success(message: __("common.success.data_deleted"));
        } catch (ServiceException $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
        catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi UserWithdrawInfoService@deleteWithdrawInfo",
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

            // Kiểm tra thông tin rút tiền thuộc user
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
                throw new ServiceException(message: __("error.wallet_not_found"));
            }

            // Tỷ giá (point -> money)
            $exchangeRate = $this->configService->getConfigValue(ConfigName::CURRENCY_EXCHANGE_RATE) ?? 1;
            // Phí rút tiền (%)
            $fee = $this->configService->getConfigValue(ConfigName::FEE_WITHDRAW_PERCENTAGE) ?? 0;
            // Tính toán số tiền rút thực tế (trừ phí rút)
            $withdrawCalc = Helper::calculateWithdrawAmount($amount, $exchangeRate, $fee);
            // Số tiền thực nhận
            $withdrawMoney = $withdrawCalc['withdraw_money'];
            // Số tiền phí rút
            $feeWithdraw = $withdrawCalc['fee_withdraw'];
            // Kiểm tra số dư trong ví tiền có đủ không để rút
            if ($wallet->balance < $withdrawMoney) {
                throw new ServiceException(message: __("error.wallet_not_enough_balance_to_withdraw"));
            }

            // Tạo transaction rút tiền
            WalletTransactionJob::dispatchSync(
                case: WalletTransCase::CREATE_WITHDRAW_REQUEST,
                data: [
                    'user_id' => $userId,
                    'withdraw_info_id' => $withdrawInfoId,
                    'amount' => $amount,
                    'withdraw_money' => $withdrawMoney,
                    'fee_withdraw' => $feeWithdraw,
                    'exchange_rate' => $exchangeRate,
                    'note' => $note,
                ]
            );
            DB::commit();

            return ServiceReturn::success(
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
