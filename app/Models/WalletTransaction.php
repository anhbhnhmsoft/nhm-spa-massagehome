<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use  HasBigIntId;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wallet_transactions';


    protected $fillable = [
        'wallet_id',
        'foreign_key',
        'transaction_code',
        'transaction_id',
        'metadata',
        'type',
        'money_amount',
        'exchange_rate_point',
        'point_amount',
        'balance_after',
        'status',
        'description',
        'expired_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'wallet_id' => 'string',
        'money_amount' => 'decimal:2',
        'exchange_rate_point' => 'decimal:2',
        'point_amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'expired_at' => 'datetime',
    ];

    /**
     * Get the wallet that owns the transaction.
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id', 'id');
    }

    public function drawInfo() {
        return $this->belongsTo(UserWithdrawInfo::class, 'foreign_key', 'id');
    }
}
