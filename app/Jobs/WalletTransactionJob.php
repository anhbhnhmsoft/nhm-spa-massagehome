<?php

namespace App\Jobs;

use App\Enums\Jobs\WalletTransCase;
use App\Enums\QueueKey;
use App\Services\Facades\TransactionJobService;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class WalletTransactionJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    // Thời gian chờ giữa các lần thử lại (giây)
    public function backoff(): array
    {
        return [30, 60, 90];
    }

    public function __construct(
        protected array          $data = [],
        protected WalletTransCase $case,
    )
    {
        $this->onQueue(QueueKey::TRANSACTIONS_PAYMENT);
    }

    public function handle(TransactionJobService $service): void
    {
        switch ($this->case) {
            case WalletTransCase::REWARD_FOR_KTV_REFERRAL:
                $service->handleRewardForKtvReferral(
                    referrerId: $this->data['referral_id'] ?? null,
                    userId: $this->data['user_id'] ?? null,
                );
                break;
            case WalletTransCase::CREATE_WITHDRAW_REQUEST:
                $service->handleCreateWithdrawRequest(
                    userId: $this->data['user_id'],
                    withdrawInfoId: $this->data['withdraw_info_id'],
                    amount: $this->data['amount'],
                    withdrawMoney: $this->data['withdraw_money'],
                    feeWithdraw: $this->data['fee_withdraw'],
                    exchangeRate: $this->data['exchange_rate'],
                    note: $this->data['note'],
                );
                break;
            case WalletTransCase::CONFIRM_WITHDRAW_REQUEST:
                $service->handleConfirmWithdrawRequest(
                    transactionId: $this->data['transaction_id'],
                );
                break;
            case WalletTransCase::CANCEL_WITHDRAW_REQUEST:
                $service->handleCancelWithdrawRequest(
                    transactionId: $this->data['transaction_id'],
                );
                break;
        }
    }
}
