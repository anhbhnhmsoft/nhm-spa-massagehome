<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    protected $total_deposit;
    protected $total_withdrawal;

    public function __construct($resource, $totalDeposit, $totalWithdrawal)
    {
        parent::__construct($resource);
        $this->total_deposit = $totalDeposit;
        $this->total_withdrawal = $totalWithdrawal;
    }


    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'balance' => $this->balance,
            'is_active' => $this->is_active,
            'total_deposit' => (string)($this->total_deposit ?? 0),
            'total_withdrawal' => (string)($this->total_withdrawal ?? 0),
        ];
    }
}
