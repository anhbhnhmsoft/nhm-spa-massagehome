<?php

namespace App\Console\Commands;

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\WalletTransaction;
use Illuminate\Console\Command;

class CheckExpiredDepositCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-expired-deposit-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired pending deposit transactions and mark them as failed.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredDepositTypes = WalletTransactionType::incomeStatus();

        $query = WalletTransaction::query()
            ->where('status', WalletTransactionStatus::PENDING->value)
            ->whereIn('type', $expiredDepositTypes)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now());

        $count = $query->count();

        if ($count === 0) {
            $this->info('No expired pending deposit transactions found.');
            return self::SUCCESS;
        }

        $query->update([
            'status' => WalletTransactionStatus::FAILED->value,
        ]);

        $this->info("Marked {$count} expired pending deposit transaction(s) as failed.");

        return self::SUCCESS;
    }
}
