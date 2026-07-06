<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        $transaction = $this->resource['transaction'];

        return [
            'id' => $transaction->id,
            'type' => $transaction->type,
            'money_amount' => $transaction->money_amount,
            'exchange_rate_point' => $transaction->exchange_rate_point,
            'point_amount' => $transaction->point_amount,
            'balance_after' => $transaction->balance_after,
            'status' => $transaction->status,
            'transaction_code' => $transaction->transaction_code,
            'description' => $transaction->description,
            'created_at' => $transaction->created_at,
            'expired_at' => $transaction->expired_at,
            'detail_kind' => $this->resource['detail_kind'],
            'payment_data' => $this->resource['payment_data'],
        ];
    }
}
