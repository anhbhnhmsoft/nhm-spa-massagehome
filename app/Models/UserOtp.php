<?php

namespace App\Models;


use App\Enums\UserOtpType;
use Illuminate\Database\Eloquent\Model;

class UserOtp extends Model
{

    protected $table = 'user_otp';

    protected $fillable = [
        'phone',
        'otp_hash',
        'type',
        'attempts',
        'expired_at',
        'verified_at',
        'last_sent_at',
        'send_count',
        'ip_address',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
        'verified_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'type'=> UserOtpType::class,
    ];

    /**
     * Kiểm tra OTP có hết hạn hay không
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expired_at->isPast();
    }

    /**
     * Kiểm tra OTP đã được xác thực hay chưa
     * @return bool
     */
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

}
