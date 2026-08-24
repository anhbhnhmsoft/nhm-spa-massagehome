<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model quản lý Lời mời KTV Chủ động Matching (Proactive Invites).
 */
class KtvProactiveInvite extends Model
{
    use HasFactory, HasBigIntId;

    protected $table = 'ktv_proactive_invites';

    protected $fillable = [
        'ktv_id',
        'customer_id',
        'request_id',
        'status',
        'note',
        'expires_at',
    ];

    protected $casts = [
        'request_id' => 'integer',
        'ktv_id' => 'string',
        'customer_id' => 'string',
        'status' => InvitationStatus::class,
        'expires_at' => 'datetime',
    ];

    /**
     * KTV gửi lời mời
     */
    public function ktv(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ktv_id', 'id');
    }

    /**
     * Khách hàng nhận lời mời
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id', 'id');
    }

    /**
     * Yêu cầu dịch vụ tương ứng (nếu có)
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'request_id', 'id');
    }
}
