<?php

namespace App\Services;

use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\NotificationType;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Jobs\SendNotificationJob;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use Closure;
use Illuminate\Support\Facades\DB;

class WalletTransactionStatusService extends BaseService
{
    public function __construct(
        protected WalletRepository $walletRepository,
        protected WalletTransactionRepository $walletTransactionRepository,
    ) {
        parent::__construct();
    }

    public function approveTransaction(int|WalletTransaction $transaction): ServiceReturn
    {
        return $this->handlePendingTransaction(
            transaction: $transaction,
            actionName: 'approveTransaction',
            handler: function (WalletTransaction $pendingTransaction): ?array {
                if ($this->isIncomeTransaction($pendingTransaction)) {
                    return $this->completeIncomeTransaction($pendingTransaction);
                }

                if ($pendingTransaction->type === WalletTransactionType::WITHDRAWAL->value) {
                    return $this->completeWithdrawTransaction($pendingTransaction);
                }

                throw new ServiceException(__("error.invalid_data"));
            },
        );
    }

    public function cancelTransaction(int|WalletTransaction $transaction): ServiceReturn
    {
        return $this->handlePendingTransaction(
            transaction: $transaction,
            actionName: 'cancelTransaction',
            handler: function (WalletTransaction $pendingTransaction): ?array {
                if ($this->isIncomeTransaction($pendingTransaction)) {
                    return $this->cancelIncomeTransaction($pendingTransaction);
                }

                if ($pendingTransaction->type === WalletTransactionType::WITHDRAWAL->value) {
                    return $this->cancelWithdrawTransaction($pendingTransaction);
                }

                throw new ServiceException(__("error.invalid_data"));
            },
        );
    }

    private function handlePendingTransaction(
        int|WalletTransaction $transaction,
        string $actionName,
        Closure $handler,
    ): ServiceReturn {
        DB::beginTransaction();

        try {
            $pendingTransaction = $this->getPendingTransactionForUpdate($this->resolveTransactionId($transaction));
            $notification = $handler($pendingTransaction);

            DB::commit();

            $this->dispatchNotification($notification);

            return ServiceReturn::success();
        } catch (ServiceException $exception) {
            DB::rollBack();

            LogHelper::error(
                message: "Lỗi WalletTransactionStatusService@{$actionName}",
                ex: $exception,
            );

            return ServiceReturn::error($exception->getMessage(), $exception);
        } catch (\Throwable $exception) {
            DB::rollBack();

            LogHelper::error(
                message: "Lỗi WalletTransactionStatusService@{$actionName}",
                ex: $exception,
            );

            return ServiceReturn::error(__("common_error.server_error"), $exception);
        }
    }

    private function completeIncomeTransaction(WalletTransaction $transaction): array
    {
        $wallet = $this->getWalletForUpdate((int) $transaction->wallet_id);

        $transaction->update([
            'status' => WalletTransactionStatus::COMPLETED->value,
        ]);

        $wallet->increment('balance', (float) $transaction->point_amount);

        return [
            'user_id' => $wallet->user_id,
            'type' => NotificationType::DEPOSIT_SUCCESS,
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->point_amount,
                'deposit_time' => $transaction->created_at?->format('Y-m-d H:i:s'),
            ],
        ];
    }

    private function completeWithdrawTransaction(WalletTransaction $transactionWithdraw): array
    {
        $wallet = $this->getWalletForUpdate((int) $transactionWithdraw->wallet_id);
        $transactionWithdrawFee = $this->getPendingWithdrawFeeTransactionForUpdate(
            walletId: (int) $wallet->id,
            transactionId: (int) $transactionWithdraw->id,
        );

        $transactionWithdraw->update([
            'status' => WalletTransactionStatus::COMPLETED->value,
        ]);

        $transactionWithdrawFee->update([
            'status' => WalletTransactionStatus::COMPLETED->value,
        ]);

        $wallet->decrement('frozen_balance', (float) $transactionWithdraw->point_amount);
        $wallet->decrement('frozen_balance', (float) $transactionWithdrawFee->point_amount);

        return [
            'user_id' => $wallet->user_id,
            'type' => NotificationType::WALLET_WITHDRAW,
            'data' => [],
        ];
    }

    private function cancelIncomeTransaction(WalletTransaction $transaction): array
    {
        $wallet = $this->getWalletForUpdate((int) $transaction->wallet_id);

        $transaction->update([
            'status' => WalletTransactionStatus::CANCELLED->value,
        ]);

        return $this->cancelNotification($wallet, $transaction);
    }

    private function cancelWithdrawTransaction(WalletTransaction $transactionWithdraw): array
    {
        $wallet = $this->getWalletForUpdate((int) $transactionWithdraw->wallet_id);
        $transactionWithdrawFee = $this->getPendingWithdrawFeeTransactionForUpdate(
            walletId: (int) $wallet->id,
            transactionId: (int) $transactionWithdraw->id,
        );

        $transactionWithdraw->update([
            'status' => WalletTransactionStatus::CANCELLED->value,
        ]);

        $transactionWithdrawFee->update([
            'status' => WalletTransactionStatus::CANCELLED->value,
        ]);

        $wallet->decrement('frozen_balance', (float) $transactionWithdraw->point_amount);
        $wallet->decrement('frozen_balance', (float) $transactionWithdrawFee->point_amount);
        $wallet->increment('balance', (float) $transactionWithdraw->point_amount);
        $wallet->increment('balance', (float) $transactionWithdrawFee->point_amount);

        return $this->cancelNotification($wallet, $transactionWithdraw);
    }

    private function getPendingTransactionForUpdate(int $transactionId): WalletTransaction
    {
        $transaction = $this->walletTransactionRepository
            ->queryTransaction()
            ->where('id', $transactionId)
            ->where('status', WalletTransactionStatus::PENDING->value)
            ->lockForUpdate()
            ->first();

        if (!$transaction) {
            throw new ServiceException(__("error.transaction_not_found"));
        }

        return $transaction;
    }

    private function getPendingWithdrawFeeTransactionForUpdate(int $walletId, int $transactionId): WalletTransaction
    {
        $transaction = $this->walletTransactionRepository
            ->queryTransaction()
            ->where('wallet_id', $walletId)
            ->where('foreign_key', $transactionId)
            ->where('type', WalletTransactionType::FEE_WITHDRAW->value)
            ->where('status', WalletTransactionStatus::PENDING->value)
            ->lockForUpdate()
            ->first();

        if (!$transaction) {
            throw new ServiceException(__("error.transaction_not_found"));
        }

        return $transaction;
    }

    private function getWalletForUpdate(int $walletId): Wallet
    {
        $wallet = $this->walletRepository
            ->queryWallet()
            ->where('id', $walletId)
            ->lockForUpdate()
            ->first();

        if (!$wallet) {
            throw new ServiceException(__("error.wallet_not_found"));
        }

        return $wallet;
    }

    private function isIncomeTransaction(WalletTransaction $transaction): bool
    {
        return in_array($transaction->type, WalletTransactionType::incomeStatus(), true);
    }

    private function cancelNotification(Wallet $wallet, WalletTransaction $transaction): array
    {
        return [
            'user_id' => $wallet->user_id,
            'type' => NotificationType::WALLET_TRANSACTION_CANCELLED,
            'data' => [
                'transaction_code' => $transaction->transaction_code,
            ],
        ];
    }

    private function dispatchNotification(?array $notification): void
    {
        if (!$notification) {
            return;
        }

        SendNotificationJob::dispatch(
            userId: $notification['user_id'],
            type: $notification['type'],
            data: $notification['data'] ?? [],
        );
    }

    private function resolveTransactionId(int|WalletTransaction $transaction): int
    {
        if ($transaction instanceof WalletTransaction) {
            return (int) $transaction->id;
        }

        return $transaction;
    }
}
