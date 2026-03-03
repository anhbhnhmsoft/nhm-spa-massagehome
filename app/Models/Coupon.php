<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

class Coupon extends Model
{
    use HasBigIntId, HasTranslations;

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
        'config',
        'count_collect',
        'user_id'
    ];

    protected $casts = [
        'id' => 'string',
        'created_by' => 'string',
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
        'count_collect' => 'integer',
    ];

    // Người tạo mã (Admin/Staff/User)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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

    /**
     * Lấy danh sách những người dùng đã sử dụng mã giảm giá này.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function couponUseds()
    {
        return $this->hasMany(CouponUsed::class, 'coupon_id', 'id');
    }
}
