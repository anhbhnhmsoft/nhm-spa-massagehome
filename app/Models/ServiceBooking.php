<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Core\GenerateId\HasBigIntId;

class ServiceBooking extends Model
{
    use SoftDeletes, HasBigIntId;

    protected $fillable = [
        'user_id',
        'service_id',
        'coupon_id',
        'duration',
        'booking_time',
        'start_time',
        'end_time',
        'status', // Cast Enum BookingStatus
        'price',
        'price_before_discount',
        'payment_type',
        'note',
        'address',
        'latitude',
        'longitude'
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'service_id' => 'string',
        'coupon_id' => 'string',
        'duration' => 'integer',
        'booking_time' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'price' => 'decimal:2',
        'price_before_discount' => 'decimal:2',
        'payment_type' => 'integer',
        'latitude' => 'decimal:10,8',
        'longitude' => 'decimal:11,8',
    ];

    // Lấy thông tin khách hàng đặt
    public function user() // Khách hàng đặt
    {
        return $this->belongsTo(User::class);
    }

    // Lấy thông tin dịch vụ được đặt
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    // Lấy thông tin mã giảm giá áp dụng (nếu có)
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
