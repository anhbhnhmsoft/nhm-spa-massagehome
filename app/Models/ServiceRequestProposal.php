<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use App\Enums\ProposalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model quản lý Lượt đề xuất KTV cho Yêu cầu dịch vụ.
 */
class ServiceRequestProposal extends Model
{
    use HasFactory, HasBigIntId, SoftDeletes;

    protected $table = 'service_request_proposals';

    protected $fillable = [
        'request_id',
        'ktv_id',
        'cskh_id',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'request_id' => 'integer',
        'ktv_id' => 'string',
        'cskh_id' => 'string',
        'status' => ProposalStatus::class,
        'expires_at' => 'datetime',
    ];

    /**
     * Yêu cầu dịch vụ tương ứng
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'request_id', 'id');
    }

    /**
     * KTV được đề xuất
     */
    public function ktv(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ktv_id', 'id');
    }

    /**
     * Nhân viên CSKH gửi đề xuất
     */
    public function cskh(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'cskh_id', 'id');
    }
}
