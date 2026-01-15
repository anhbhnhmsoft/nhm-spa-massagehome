<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class WalletTransactionRepository extends BaseRepository
{

    protected function getModel(): string
    {
        return WalletTransaction::class;
    }

    public function queryTransaction(): Builder
    {
        return $this->model->query();
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        if (isset($filters['wallet_id'])) {
            $query->where('wallet_id', $filters['wallet_id']);
        }
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        return $query;
    }

    public function sortQuery(Builder $query, ?string $sortBy, string $direction): Builder
    {
        // Mặc định sắp xếp theo created_at desc
        $query->orderBy('created_at', "desc");
        return $query;
    }

    /**
     * Tổng lợi nhuận Affiliate trong khoảng thời gian
     * @param int $walletId
     * @param Carbon $from
     * @param Carbon $to
     * @return int
     */
    public function sumAffiliateProfit(int $walletId, Carbon $from, Carbon $to): int
    {
        return $this->queryTransaction()
            ->where('wallet_id', $walletId)
            ->where('type', WalletTransactionType::AFFILIATE->value)
            ->where('status', WalletTransactionStatus::COMPLETED->value)
            ->whereBetween('created_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->sum('point_amount');
    }

    /**
     * Tổng lợi nhuận của các KTV mà mình giới thiệu trong khoảng thời gian
     * @param int $walletId
     * @param Carbon $from
     * @param Carbon $to
     * @return int
     */
    public function sumReferralKtvProfit(int $walletId, Carbon $from, Carbon $to): int
    {
        return $this->queryTransaction()
            ->where('wallet_id', $walletId)
            ->where('type', WalletTransactionType::REFERRAL_KTV->value)
            ->where('status', WalletTransactionStatus::COMPLETED->value)
            ->whereBetween('created_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->sum('point_amount');
    }
}
