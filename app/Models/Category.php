<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Core\GenerateId\HasBigIntId;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use  HasBigIntId, HasTranslations;

    protected $translatable = [
        'name',
        'description',
    ];

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'image_url',
        'description',
        'position',
        'is_featured',
        'usage_count',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'position' => 'integer',
        'is_featured' => 'boolean',
        'usage_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public function services()
    {
        return $this->hasMany(Service::class, 'category_id');
    }

    // Quan hệ 1-n với bảng category_prices.
    public function prices()
    {
        return $this->hasMany(CategoryPrice::class, 'category_id');
    }

    // Lấy 1 bản ghi có giá thấp nhất trong bảng category_prices
    public function cheapestPrice()
    {
        return $this->hasOne(CategoryPrice::class)->oldest('price');
    }

    // Quan hệ n-n với bảng users.
    // Lấy danh sách KTV có sẵn dịch vụ trong danh mục này
    public function users()
    {
        return $this->belongsToMany(User::class, 'services', 'category_id', 'user_id')
            ->withTimestamps();
    }

    // Quan hệ 1-n với bảng service_bookings.
    public function bookings()
    {
        return $this->hasMany(ServiceBooking::class, 'category_id');
    }
}
