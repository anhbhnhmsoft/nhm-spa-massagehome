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
    use HasFactory, SoftDeletes , HasBigIntId, HasTranslations;

    protected $translatable = [
        'label',
        'description',
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
}

