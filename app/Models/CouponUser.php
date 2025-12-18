<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponUser extends Model
{
    protected $table = 'coupon_users';

    protected $fillable = [
        'coupon_id',
        'user_id',
        'is_used',
    ];

    protected $casts = [
        'id' => 'string',
        'coupon_id' => 'string',
        'user_id' => 'string',
        'is_used' => 'boolean',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
