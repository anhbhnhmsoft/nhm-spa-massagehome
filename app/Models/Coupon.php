<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

class Coupon extends Model
{
    use HasFactory, SoftDeletes, HasBigIntId, HasTranslations;

    protected $translatable = [
        'label',
        'description',
        'banners'
    ];

    protected $table = 'coupons';

    protected $fillable = [
        'code',
        'label',
        'description',
        'created_by',
        'for_service_id',
        'is_percentage',
        'discount_value',
        'max_discount',
        'start_at',
        'end_at',
        'usage_limit',
        'used_count',
        'is_active',
        'banners',
        'display_ads',
        'config'
    ];

    protected $casts = [
        'id' => 'string',
        'created_by' => 'string',
        'for_service_id' => 'string',
        'is_percentage' => 'boolean',
        'is_active' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'discount_value' => 'float',
        'max_discount' => 'float',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'display_ads' => 'boolean',
        'config' => 'array',
        /**
         * Cấu trúc config dự kiến:
         * - per_day_global (int): Tổng mã tối đa được thu thập trong 1 ngày toàn hệ thống.
         * - min_order_value (float): Giá trị đơn hàng tối thiểu để áp dụng mã. 
         * - used_day (array): ['date' => 'Y-m-d', 'count' => int] - Theo dõi số lượng đã dùng theo ngày thực tế.
         * - collected_day (array): ['date' => 'Y-m-d', 'count' => int] - Theo dõi số lượng đã thu theo ngày thực tế.
         * - allowed_time_slots (array): Danh sách các khung giờ vàng cho phép sử dụng mã.
         */
    ];

    // Người tạo mã (Admin/Staff/User)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Dịch vụ áp dụng (Nếu null là áp dụng tất cả)
    public function service()
    {
        return $this->belongsTo(Service::class, 'for_service_id');
    }


    // Lấy các mã còn hạn sử dụng (Thời gian + Số lượng)
    public function scopeValid($query)
    {
        $now = Carbon::now();
        return $query->active()
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereColumn('used_count', '<', 'usage_limit');
            });
    }

    /**
     * Lấy danh sách những người dùng đang sở hữu mã giảm giá này.
     */
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'coupon_users',
            'coupon_id',
            'user_id'
        )
            ->withPivot('is_used')
            ->withTimestamps();
    }
}
