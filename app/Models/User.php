<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use App\Enums\UserRole;
use App\Enums\UserFileType;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasBigIntId, HasApiTokens;

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
        'referred_at',
        'last_login_at',
        'device_id',
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
        'referred_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            $avatarPath = $user->profile->avatar_url;
            // 1. Xóa Avatar
            if ($avatarPath) {
                Storage::disk('public')->delete($avatarPath);
            }
            // 2. Xóa file đính kèm khác (CCCD mặt trước, mặt sau, ảnh KTV...)
            if ($user->files) {
                foreach ($user->files as $file) {
                    if ($file->file_path) {
                        if ($file->is_public) {
                            Storage::disk('public')->delete($file->file_path);
                        }else{
                            Storage::disk('private')->delete($file->file_path);
                        }
                    }
                }
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
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

    public function faceWithIdentityCard()
    {
        return $this->hasOne(UserFile::class)->where('type', UserFileType::FACE_WITH_IDENTITY_CARD);
    }

    public function gallery()
    {
        return $this->hasMany(UserFile::class)->where('type', UserFileType::KTV_IMAGE_DISPLAY);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Lấy danh sách các category mà Kĩ thuật viên này đăng ký làm
     * @return BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'services', 'user_id', 'category_id')
            ->withPivot('id','performed_count') // lấy ID của service và số lần thực hiện dịch vụ
            ->withTimestamps();
    }

    /**
     * Lấy danh sách các service mà Kĩ thuật viên này đăng ký làm (quan hệ n-n với category)
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
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

    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
            ->orderBy('created_at', 'desc');
    }

    public function mobileNotifications()
    {
        return $this->hasMany(MobileNotification::class, 'user_id'); // hoặc notifiable_id tùy cấu trúc cũ
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

    /**
     * Lấy danh sách KTV mà User này ĐÃ ĐĂNG KÍ với
     */
    public function ktvsUnderAgency()
    {
        return $this->hasManyThrough(
            User::class,
            UserReviewApplication::class,
            'agency_id',
            'id',
            'id',
            'user_id'
        );
    }

    public function schedule()
    {
        return $this->hasOne(UserKtvSchedule::class, 'ktv_id');
    }

    /**
     * Lấy danh sách các hồ sơ ứng tuyển/KTV mà user này giới thiệu
     */
    public function managedApplications()
    {
        return $this->hasMany(UserReviewApplication::class, 'referrer_id');
    }


}
