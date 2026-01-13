<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use App\Enums\ReviewApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;
class UserReviewApplication extends Model
{
    use SoftDeletes, HasBigIntId, HasTranslations;

    protected $table = 'user_review_application';

    public array $translatable = ['bio'];


    protected $fillable = [
        'user_id',
        'referrer_id',
        'status', // Cast Enum
        'province_code',
        'address',
        'latitude',
        'longitude',
        'bio',
        'experience',
        'note',
        'effective_date',
        'application_date',
        'role',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'referrer_id' => 'string',
        'latitude' => 'float',
        'longitude' => 'float',
        'status' => ReviewApplicationStatus::class,
        'note' => 'string',
        'effective_date' => 'date',
        'application_date' => 'date',
    ];

    /**
     * Lấy thông tin về người giới thiệu.
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Lấy thông tin về người được review.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Lấy thông tin về tỉnh/thành phố.
     */
    public function province()
    {
        return $this->hasOne(Province::class, 'code', 'province_code');
    }
}
