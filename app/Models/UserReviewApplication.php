<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use App\Enums\ReviewApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserReviewApplication extends Model
{
    use SoftDeletes, HasBigIntId;

    protected $table = 'user_review_application';

    protected $fillable = [
        'user_id',
        'status', // Cast Enum
        'province_code',
        'address',
        'latitude',
        'longitude',
        'bio',
        'experience',
        'skills',
        'note',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'skills' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'status' => ReviewApplicationStatus::class,
        'note' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function province()
    {
        return $this->hasOne(Province::class, 'code', 'province_code');
    }
}
