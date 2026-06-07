<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Core\GenerateId\HasBigIntId;

class ServiceBooking extends Model
{
    use HasBigIntId;

    protected $fillable = [
        'user_id',
        'ktv_user_id',
        'original_ktv_user_id',
        'category_id',
        'coupon_id',
        'duration',
        'booking_time',
        'start_time',
        'end_time',
        'status', // Cast Enum BookingStatus
        'price',
        'price_discount',
        'price_transportation',
        'payment_type',
        'note',
        'address',
        'latitude',
        'longitude',
        'ktv_address',
        'ktv_latitude',
        'ktv_longitude',
        'reason_cancel',
        'overtime_warning_sent',
        'cancel_by',
        'ktv_confirm_deadline_at',
        'application_opened_at',
        'application_open_reason',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'ktv_user_id' => 'string',
        'original_ktv_user_id' => 'string',
        'category_id' => 'string',
        'coupon_id' => 'string',
        'duration' => 'integer',
        'booking_time' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'price' => 'decimal:2',
        'price_discount' => 'decimal:2',
        'price_transportation' => 'decimal:2',
        'payment_type' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'overtime_warning_sent' => 'boolean',
        'cancel_by' => 'integer',
        'ktv_confirm_deadline_at' => 'datetime',
        'application_opened_at' => 'datetime',
    ];

    // Lấy thông tin khách hàng đặt
    public function user() // Khách hàng đặt
    {
        return $this->belongsTo(User::class);
    }

    // Lấy thông tin KTV thực hiện dịch vụ
    public function ktvUser()
    {
        return $this->belongsTo(User::class, 'ktv_user_id');
    }

    public function originalKtvUser()
    {
        return $this->belongsTo(User::class, 'original_ktv_user_id');
    }

    // Lấy thông tin dịch vụ được đặt
    public function service()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // Lấy thông tin mã giảm giá áp dụng (nếu có)
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function option() // Option dịch vụ
    {
        return $this->belongsTo(ServiceOption::class);
    }

    // Lấy thông tin đánh giá (nếu có)
    public function reviews()
    {
        return $this->hasMany(Review::class, 'service_booking_id');
    }

    public function applications()
    {
        return $this->hasMany(BookingApplication::class, 'booking_id');
    }


}
