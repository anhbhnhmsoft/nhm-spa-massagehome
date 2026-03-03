<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Core\Helper;
use App\Enums\PaymentType;
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
     * Tạo giao dịch thanh toán dịch vụ cho khách hàng
     * @param int $walletCustomerId
     * @param int $bookingId
     * @param float $price
     * @param float $exchangeRate
     * @param float $balanceAfter
     * @return void
     */
    public function createPaymentServiceBookingForCustomer(
        int $walletCustomerId,
        int $bookingId,
        float $price,
        float $exchangeRate,
        float $balanceAfter,
    )
    {
        $this->create([
            'wallet_id' => $walletCustomerId,
            'foreign_key' => $bookingId,
            'money_amount' => $price * $exchangeRate,
            'exchange_rate_point' => $exchangeRate,
            'point_amount' => $price,
            'balance_after' => $balanceAfter,
            'type' => WalletTransactionType::PAYMENT->value,
            'status' => WalletTransactionStatus::COMPLETED->value,
            'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
            'description' => __('booking.payment.wallet_customer'),
            'expired_at' => now(),
            'transaction_id' => null,
            'metadata' => null,
        ]);
    }


    /**
     * Lấy thống kê tiền vào và ra hệ thống trong khoảng thời gian
     * @param Carbon $from
     * @param Carbon $to
     */
    public function getFinancialInOutStats(Carbon $from, Carbon $to)
    {
        // Trạng thái tiền vào system
        $incomeTypes = WalletTransactionType::incomeStatus();
        // Trạng thái tiền ra system
        $outcomeTypes = WalletTransactionType::outcomeStatus();

        return $this->query()
            ->selectRaw("
                SUM(CASE WHEN type IN (" . implode(',', $incomeTypes) . ") THEN point_amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type IN (" . implode(',', $outcomeTypes) . ") THEN point_amount ELSE 0 END) as total_outcome
            ")
            ->where('status', WalletTransactionStatus::COMPLETED->value)
            ->whereBetween('created_at', [$from, $to])
            ->first();
    }


    /**
     * Lấy toàn bộ thống kê tài chính dashboard admin trong khoảng thời gian
     * @param Carbon $from
     * @param Carbon $to
     */
    public function getFinancialDashboardStats(Carbon $from, Carbon $to)
    {
        // Trạng thái lợi nhuận
        $revenueStatus = WalletTransactionType::revenueStatus();

        // Trạng thái chi phí vận hành
        $operationCostTypes = WalletTransactionType::operationCostStatus();

        // Trạng thái chi phí vận chuyển
        $transportTypes = WalletTransactionType::transportStatus();

        // Trạng thái chi phí khách hàng
        $customerCostTypes = WalletTransactionType::customerCostStatus();

        // Các loại phí riêng cho Agency
        $agencyCostTypes = WalletTransactionType::agencyCostStatus();

        // Các loại phí riêng cho KTV
        $technicalCostTypes = WalletTransactionType::technicalCostStatus();

        return $this->query()
            ->join('wallets', 'wallet_transactions.wallet_id', '=', 'wallets.id')
            ->join('users', 'wallets.user_id', '=', 'users.id')
            ->where('wallet_transactions.status', WalletTransactionStatus::COMPLETED->value)
            ->whereBetween('wallet_transactions.created_at', [$from, $to])
            ->selectRaw("
            SUM(CASE
                WHEN wallet_transactions.type IN (" . implode(',', $revenueStatus) . ")
                THEN wallet_transactions.point_amount
                ELSE 0
            END) as total_revenue,

            SUM(CASE
                WHEN wallet_transactions.type IN (" . implode(',', $operationCostTypes) . ")
                THEN wallet_transactions.point_amount
                ELSE 0
            END) as operation_cost,

            SUM(CASE
                WHEN wallet_transactions.type IN (" . implode(',', $transportTypes) . ")
                THEN wallet_transactions.point_amount
                ELSE 0
            END) as transportation_cost,

            SUM(CASE
                WHEN users.role = ?
                     AND wallet_transactions.type IN (" . implode(',', $customerCostTypes) . ")
                THEN wallet_transactions.point_amount
                ELSE 0
            END) as customer_cost,

            SUM(CASE
                WHEN users.role = ?
                     AND wallet_transactions.type IN (" . implode(',', $agencyCostTypes) . ")
                THEN wallet_transactions.point_amount
                ELSE 0
            END) as agency_cost,

            SUM(CASE
                WHEN users.role = ?
                     AND wallet_transactions.type IN (" . implode(',', $technicalCostTypes) . ")
                THEN wallet_transactions.point_amount
                ELSE 0
            END) as technical_cost
            ", [
                UserRole::CUSTOMER->value,
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

    /**
     * Tổng lợi nhuận thực tế từ các booking đã thanh toán của 1 KTV trong khoảng thời gian
     * @param int $ktvUserId - ID của KTV
     * @param Carbon $from
     * @param Carbon $to
     */
    public function sumRealIncomePaymentBooking(int $ktvUserId, Carbon $from, Carbon $to)
    {
        $incomeData = $this->query()
            ->whereHas('wallet.user', function (Builder $query) use ($ktvUserId) {
                $query->where('user_id', $ktvUserId);
            })
            ->where('status', WalletTransactionStatus::COMPLETED->value)
            ->whereBetween('created_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->selectRaw("
                    SUM(point_amount) FILTER (WHERE type = ?) as total_received,
                    SUM(point_amount) FILTER (WHERE type = ?) as total_retrieve
                ", [
                WalletTransactionType::PAYMENT_FOR_KTV->value,
                WalletTransactionType::RETRIEVE_PAYMENT_REFUND_KTV->value
            ])
            ->first();
        return ($incomeData->total_received ?? 0) - ($incomeData->total_retrieve ?? 0);
    }

    /**
     * Lấy thông tin transaction rút tiền đang chờ duyệt theo ID
     * @param int $transactionId
     * @return WalletTransaction|null
     */
    public function getWithdrawPendingTransactionById(int $transactionId)
    {
        return $this->query()
            ->where('id', $transactionId)
            ->where('status', WalletTransactionStatus::PENDING->value)
            ->where('type', WalletTransactionType::WITHDRAWAL->value)
            ->first();
    }
}
