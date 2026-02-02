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

    public $tries = 1;

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
        }
    }
}
