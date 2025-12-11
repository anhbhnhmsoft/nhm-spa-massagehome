<?php

namespace App\Services;

use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;

class PaymentService extends BaseService
{
    public function __construct(
        protected WalletRepository $walletRepository,
        protected WalletTransactionRepository $walletTransactionRepository,
    )
    {
        parent::__construct();
    }

    public function getUserWallet(int $userId): ServiceReturn
    {
        try {
            $wallet = $this->walletRepository->queryWallet()
                ->where('user_id', $userId)
                ->first();
            if (!$wallet) {
                throw new ServiceException(
                    message: __("error.wallet_not_found")
                );
            }
            return ServiceReturn::success(
                data: $wallet
            );
        }catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
        catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi PaymentService@getUserWallet",
                ex: $exception
            );
           return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }

    }
}
