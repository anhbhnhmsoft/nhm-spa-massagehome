<?php

namespace App\Models;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\GenerateId\HasBigIntId;
use App\Enums\UserRole;
use App\Enums\UserFileType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasBigIntId, HasApiTokens;

    protected $fillable = [
        'id',
        'phone',
        'phone_verified_at',
        'password',
        'name',
        'role', // Cast enum UserRole
        'language',
        'is_active',
        'referred_by_user_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'id' => 'string',
        'referred_by_user_id' => 'string',
        'phone_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = ['is_online'];

    public function getIsOnlineAttribute()
    {
        return Caching::hasCache(
            key: CacheKey::CACHE_USER_HEARTBEAT,
            uniqueKey: $this->id
        );
    }

    // Relations
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function reviewApplication()
    {
        return $this->hasOne(UserReviewApplication::class);
    }

    public function getAgencyReviewsAttribute()
    {
        return $this->hasOne(UserReviewApplication::class)->where('role', UserRole::AGENCY->value)->latestOfMany();
    }

    public function getStaffReviewsAttribute()
    {
        return $this->hasOne(UserReviewApplication::class)->where('role', UserRole::KTV->value)->latestOfMany();
    }

    public function files()
    {
        return $this->hasMany(UserFile::class);
    }

    public function cccdFront()
    {
        return $this->hasOne(UserFile::class)->where('type', UserFileType::IDENTITY_CARD_FRONT);
    }

    public function cccdBack()
    {
        return $this->hasOne(UserFile::class)->where('type', UserFileType::IDENTITY_CARD_BACK);
    }

    public function certificate()
    {
        return $this->hasOne(UserFile::class)->where('type', UserFileType::LICENSE);
    }

    public function gallery()
    {
        return $this->hasMany(UserFile::class)->where('type', UserFileType::KTV_IMAGE_DISPLAY);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    // Người giới thiệu mình
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    // Những người mình giới thiệu
    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }

    /**
     * Lấy các đánh giá mà User này NHẬN ĐƯỢC (với tư cách là Provider/KTV)
     * Logic: reviews.user_id = users.id
     */
    public function reviewsReceived()
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    /**
     * Lấy danh sách Coupon mà User này TẠO RA.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function createdCoupons()
    {
        return $this->hasMany(Coupon::class, 'created_by');
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    // Hàm này giúp lấy mảng token để bắn thông báo
    public function routeNotificationForExpo()
    {
        return $this->devices->pluck('token')->toArray();
    }

    /**
     * Lấy danh sách Booking mà User này NHẬN ĐƯỢC (với tư cách là KTV).
     * Logic: User (Provider) -> Service -> ServiceBooking
     */
    public function jobsReceived()
    {
        return $this->hasManyThrough(
            ServiceBooking::class, // Bảng đích (Booking)
            Service::class,        // Bảng trung gian (Service)
            'user_id',             // Khóa ngoại trên bảng Service (services.user_id = user.id)
            'service_id',          // Khóa ngoại trên bảng Booking (service_bookings.service_id = service.id)
            'id',                  // Khóa chính bảng User
            'id'                   // Khóa chính bảng Service
        );
    }


    /**
     * Lấy danh sách Booking mà User này ĐẶT (với tư cách là Customer)
     */
    public function bookings()
    {
        return $this->hasMany(ServiceBooking::class, 'user_id');
    }

    /**
     * Lấy danh sách Booking mà User này ĐẶT (với tư cách là KTV)
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ktvBookings()
    {
        return $this->hasMany(ServiceBooking::class, 'ktv_user_id');
    }

    public function reviewWrited()
    {
        return $this->hasMany(Review::class, 'review_by');
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function primaryAddress()
    {
        return $this->hasOne(UserAddress::class)->where('is_primary', true);
    }

    public function collectionCoupons(): BelongsToMany
    {
        return $this->belongsToMany(
            Coupon::class,
            'coupon_users', // Tên bảng trung gian
            'user_id',      // Khóa ngoại của User trong bảng trung gian
            'coupon_id'     // Khóa ngoại của Coupon trong bảng trung gian
        )
            ->withPivot('is_used') // Lấy thêm cột is_used từ bảng trung gian
            ->withTimestamps();     // Nếu bảng coupon_users có created_at/updated_at
    }

    /**
     * Lấy danh sách Affiliate Record
     */
    public function affiliateRecords()
    {
        return $this->hasMany(AffiliateLink::class, 'referred_user_id');
    }
}
