<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use App\Enums\ProposalStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UrgencyLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model quản lý Yêu cầu dịch vụ & Matching từ Khách hàng.
 */
class ServiceRequest extends Model
{
    use HasFactory, HasBigIntId, SoftDeletes;

    protected $table = 'service_requests';

    protected $fillable = [
        'customer_id',
        'cskh_id',
        'service_id',
        'preferred_techniques',
        'province_code',
        'district_code',
        'ward_code',
        'address',
        'latitude',
        'longitude',
        'preferred_date',
        'time_slot',
        'urgency_level',
        'preferred_ktv_ids',
        'note',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'customer_id' => 'string',
        'cskh_id' => 'string',
        'service_id' => 'integer',
        'preferred_techniques' => 'array',
        'preferred_ktv_ids' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'preferred_date' => 'date',
        'urgency_level' => UrgencyLevel::class,
        'status' => ServiceRequestStatus::class,
        'expires_at' => 'datetime',
    ];

    /**
     * Khách hàng tạo yêu cầu
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id', 'id');
    }

    /**
     * CSKH phụ trách xử lý đơn
     */
    public function cskh(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'cskh_id', 'id');
    }

    /**
     * Dịch vụ mong muốn
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    /**
     * Danh sách các đề xuất KTV cho yêu cầu này
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(ServiceRequestProposal::class, 'request_id', 'id');
    }

    /**
     * Lấy đề xuất KTV đã được chấp nhận duy nhất (nếu có)
     */
    public function acceptedProposal()
    {
        return $this->hasOne(ServiceRequestProposal::class, 'request_id', 'id')
            ->whereIn('status', [ProposalStatus::CUSTOMER_ACCEPTED->value, ProposalStatus::KTV_ACCEPTED->value]);
    }
}
