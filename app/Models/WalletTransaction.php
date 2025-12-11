<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletTransaction extends Model
{
    use HasFactory, SoftDeletes, HasBigIntId;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wallet_transactions';

    /**
     * The attributes that are mass assignable.
     *Schema::create('wallet_transactions', function (Blueprint $table) {
     * $table->comment('Bảng wallet_transactions lưu trữ thông tin giao dịch ví tiền');
     * $table->id();
     * $table->unsignedBigInteger('wallet_id')->comment('ID ví tiền');
     * $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
     * $table->unsignedBigInteger('foreign_key')->nullable()->comment('Khóa ngoại liên kết với bảng khác');
     * $table->string('transaction_code')->comment('Mã giao dịch');
     * $table->string('transaction_id')->nullable()->comment('ID giao dịch bên thứ 3');
     * $table->text('metadata')->nullable()->comment('Dữ liệu bổ sung liên quan đến giao dịch');
     * $table->smallInteger('type')->comment('Loại giao dịch (trong enum TransactionType)');
     * $table->decimal('amount', 15, 2)->comment('Số tiền');
     * $table->decimal('balance_after', 15, 2)->comment('Số dư ví sau giao dịch');
     * $table->smallInteger('status')->comment('Trạng thái giao dịch (trong enum TransactionStatus)');
     * $table->string('description')->nullable()->comment('Mô tả giao dịch');
     * $table->timestamp('expired_at')->nullable()->comment('Thời gian hết hạn (nếu có)');
     * });
     * @var array<int, string>
     */
    protected $fillable = [
        'wallet_id',
        'foreign_key',
        'transaction_code',
        'transaction_id',
        'metadata',
        'type',
        'amount',
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
        'amount' => 'decimal:2',
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
}
