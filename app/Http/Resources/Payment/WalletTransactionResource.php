<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'money_amount' => $this->money_amount,
            'exchange_rate_point' => $this->exchange_rate_point,
            'point_amount' => $this->point_amount,
            'balance_after' => $this->balance_after,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
