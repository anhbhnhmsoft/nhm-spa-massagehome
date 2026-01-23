<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Enums\UserRole;
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
     * Lấy toàn bộ thống kê tài chính dashboard admin trong khoảng thời gian
     * @param Carbon $from
     * @param Carbon $to
     */
    public function getFinancialDashboardStats(Carbon $from, Carbon $to)
    {
        // Chuẩn bị các mảng Type để dùng trong câu query
        $incomeTypes = WalletTransactionType::incomeStatus();
        $operationCostTypes = WalletTransactionType::operationCostStatus();

        // Các loại phí riêng cho Agency
        $agencyTypes = [
            WalletTransactionType::AFFILIATE->value,
            WalletTransactionType::REFERRAL_KTV->value,
            WalletTransactionType::REFERRAL_INVITE_KTV_REWARD->value,
        ];

        // Các loại phí riêng cho KTV
        $ktvTypes = [
            WalletTransactionType::AFFILIATE->value,
            WalletTransactionType::REFERRAL_KTV->value,
            WalletTransactionType::REFERRAL_INVITE_KTV_REWARD->value,
            WalletTransactionType::PAYMENT_FOR_KTV->value,
        ];

        return $this->query()
            // 1. Join bảng để lấy Role của User (chỉ Join 1 lần)
            ->join('wallets', 'wallet_transactions.wallet_id', '=', 'wallets.id')
            ->join('users', 'wallets.user_id', '=', 'users.id')
            ->where('wallet_transactions.status', WalletTransactionStatus::COMPLETED->value)
            ->whereBetween('wallet_transactions.created_at', [$from, $to])
            ->selectRaw("
            SUM(CASE
                WHEN wallet_transactions.type IN (" . implode(',', $incomeTypes) . ")
                THEN wallet_transactions.point_amount
                ELSE 0
            END) as total_income,

            SUM(CASE
                WHEN wallet_transactions.type IN (" . implode(',', $operationCostTypes) . ")
                THEN wallet_transactions.point_amount
                ELSE 0
            END) as operation_cost,

            SUM(CASE
                WHEN wallet_transactions.type = ?
                THEN wallet_transactions.point_amount
                ELSE 0
            END) as affiliate_cost,

            SUM(CASE
                WHEN users.role = ?
                     AND wallet_transactions.type IN (" . implode(',', $agencyTypes) . ")
                THEN wallet_transactions.point_amount
                ELSE 0
            END) as agency_cost,

            SUM(CASE
                WHEN users.role = ?
                     AND wallet_transactions.type IN (" . implode(',', $ktvTypes) . ")
                THEN wallet_transactions.point_amount
                ELSE 0
            END) as ktv_cost

            ", [
                WalletTransactionType::AFFILIATE->value,
                UserRole::AGENCY->value,
                UserRole::KTV->value
            ])
            ->first();
    }

    /**
     * Tổng lợi nhuận Affiliate trong khoảng thời gian của 1 ví
     * @param int $walletId
     * @param Carbon $from
     * @param Carbon $to
     */
    public function sumAffiliateProfit(int $walletId, Carbon $from, Carbon $to)
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
     */
    public function sumReferralKtvProfit(int $walletId, Carbon $from, Carbon $to)
    {
        return $this->queryTransaction()
            ->where('wallet_id', $walletId)
            ->where('type', WalletTransactionType::REFERRAL_KTV->value)
            ->where('status', WalletTransactionStatus::COMPLETED->value)
            ->whereBetween('created_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->sum('point_amount');
    }

    /**
     * Tổng số yêu cầu rút tiền đang chờ duyệt trong khoảng thời gian
     * @param Carbon $from
     * @param Carbon $to
     * @return int
     */
    public function countTotalWithdrawPendingRequestTransaction(Carbon $from, Carbon $to)
    {
        return $this->queryTransaction()
            ->where('type', WalletTransactionType::WITHDRAWAL->value)
            ->where('status', WalletTransactionStatus::PENDING->value)
            ->whereBetween('created_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->count();
    }
}
