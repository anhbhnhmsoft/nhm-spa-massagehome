<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Model;

class AffiliateLink extends Model
{
    use  HasBigIntId;

    protected $fillable = [
        'client_ip',
        'user_agent',
        'referrer_id',      // lưu ID người giới thiệu
        'referred_user_id', // ID của người được giới thiệu (User mới đăng ký/đăng nhập)
        'is_matched',
        'expired_at',
    ];

    protected $casts = [
        'id' => 'string',
        'referrer_id' => 'string',
        'referred_user_id' => 'string',
        'is_matched' => 'boolean',
        'expired_at' => 'datetime',
    ];

    // Mối quan hệ với người giới thiệu
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }
}
