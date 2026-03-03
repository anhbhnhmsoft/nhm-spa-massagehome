<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasBigIntId;


    protected $fillable = [
        'user_id',
        'category_id',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'category_id' => 'string',
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
}
