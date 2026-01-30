<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Service extends Model
{
    use HasBigIntId, HasTranslations;

    protected $translatable = [
        'name',
        'description',
    ];

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'description',
        'is_active',
        'image_url',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'category_id' => 'string',
        'is_active' => 'boolean',
    ];



    /**
     * Mối quan hệ với User (Provider) - là người làm massage
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function provider()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Mối quan hệ với Category
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Mối quan hệ với ServiceBooking
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bookings()
    {
        return $this->hasMany(ServiceBooking::class);
    }

    /**
     * Mối quan hệ với ServiceOption
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function options()
    {
        return $this->hasMany(ServiceOption::class);
    }

    /**
     * Mối quan hệ n-n với bảng category_prices thông qua bảng service_options
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function optionCategoryPrices()
    {
        // Tham số thứ 2 là tên bảng trung gian: 'service_options'
        return $this->belongsToMany(
            CategoryPrice::class,
            'service_options',
            'service_id',
            'category_price_id'
        )->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasManyThrough(
            Review::class,
            ServiceBooking::class,
            'service_id',
            'service_booking_id',
            'id',
            'id'
        );
    }
}
