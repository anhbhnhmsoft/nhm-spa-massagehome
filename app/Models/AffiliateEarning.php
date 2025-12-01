<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AffiliateEarning extends Model
{
    use HasFactory, SoftDeletes, HasBigIntId;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'affiliate_earnings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'affiliate_user_id',
        'referred_user_id',
        'transaction_id',
        'commission_amount',
        'commission_rate',
        'status',
        'processed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'affiliate_user_id' => 'string',
        'referred_user_id' => 'string',
        'transaction_id' => 'string',
        'commission_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the affiliate user that earned the commission.
     */
    public function affiliateUser()
    {
        return $this->belongsTo(User::class, 'affiliate_user_id');
    }

    /**
     * Get the referred user that generated the commission.
     */
    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    /**
     * Get the transaction that generated the commission.
     */
    public function transaction()
    {
        return $this->belongsTo(WalletTransaction::class, 'transaction_id');
    }
}
