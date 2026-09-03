<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use App\Enums\ReviewApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;

class UserReviewApplication extends Model
{
    use HasBigIntId, HasTranslations;

    protected $table = 'user_review_application';

    public array $translatable = ['bio'];

    protected $fillable = [
        'user_id',
        'referrer_id',
        'nickname',
        'status', // Cast Enum
        'bio',
        'experience',
        'note',
        'effective_date',
        'application_date',
        'role',
        'is_leader',
        'is_priority',
        'address',
        'latitude',
        'longitude',
        'service_performed_count',
        'contact_phone',
        'contact_verified',
        'portrait_verified',
        'portrait_verified_at',
        'certificate_verified',
        'certificates',
        'techniques',
        'strength_service_ids',
        'province_code',
        'district_code',
        'ward_code',
        'priority_areas',
        'service_locations',
        'work_province',
        'work_wards',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'referrer_id' => 'string',
        'status' => ReviewApplicationStatus::class,
        'note' => 'string',
        'effective_date' => 'date',
        'application_date' => 'date',
        'is_leader' => 'boolean',
        'is_priority' => 'boolean',
        'contact_verified' => 'boolean',
        'portrait_verified' => 'boolean',
        'portrait_verified_at' => 'datetime',
        'certificate_verified' => 'boolean',
        'certificates' => 'array',
        'techniques' => 'array',
        'strength_service_ids' => 'array',
        'priority_areas' => 'array',
        'service_locations' => 'array',
    ];

    /**
     * Getter & Setter cho work_wards: Luôn trả về 1 Phường/Xã (chuỗi đơn) cho giao diện
     */
    protected function workWards(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (blank($value)) {
                    return null;
                }
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return $decoded[0] ?? null;
                }
                return $value;
            },
            set: function ($value) {
                if (blank($value)) {
                    return null;
                }
                if (is_array($value)) {
                    $first = array_values(array_filter($value))[0] ?? null;
                    return $first ? json_encode([$first]) : null;
                }
                return json_encode([$value]);
            }
        );
    }

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
}
