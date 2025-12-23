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
        'agency_id',
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
        'agency_id' => 'string',
        'latitude' => 'float',
        'longitude' => 'float',
        'status' => ReviewApplicationStatus::class,
        'note' => 'string',
        'effective_date' => 'date',
        'application_date' => 'date',
    ];

    /**
     * Lấy thông tin về đại lý.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function agency()
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function province()
    {
        return $this->hasOne(Province::class, 'code', 'province_code');
    }
}
