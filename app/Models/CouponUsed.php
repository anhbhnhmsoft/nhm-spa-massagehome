<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CouponUsed extends Model
{
    use SoftDeletes;

    protected $table = 'coupon_used';

    protected $fillable = [
        'coupon_id',
        'user_id',
        'service_id',
        'booking_id',
    ];

    protected $casts = [
        'coupon_id' => 'string',
        'user_id' => 'string',
        'service_id' => 'string',
        'booking_id' => 'string',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function booking()
    {
        return $this->belongsTo(ServiceBooking::class, 'booking_id');
    }
}
