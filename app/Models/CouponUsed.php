<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponUsed extends Model
{

    protected $table = 'coupon_used';

    protected $fillable = [
        'coupon_id',
        'user_id',
        'booking_id',
    ];

    protected $casts = [
        'coupon_id' => 'string',
        'user_id' => 'string',
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

    public function booking()
    {
        return $this->belongsTo(ServiceBooking::class, 'booking_id');
    }
}
