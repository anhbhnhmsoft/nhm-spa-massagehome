<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class CategoryPrice extends Model
{
    use HasBigIntId;

    protected $table = 'category_prices';

    protected $fillable = [
        'category_id',
        'price',
        'duration',
    ];

    protected $casts = [
        'id' => 'string',
        'category_id' => 'string',
        'price' => 'decimal:2',
    ];

    // Quan hệ 1-n với bảng categories.
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    protected function labelText()
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                // Kiểm tra xem dữ liệu có tồn tại không để tránh lỗi null
                $duration = $attributes['duration'] ?? 0;
                $price = $attributes['price'] ?? 0;

                return $duration . ' phút - ' . number_format($price, 0, ',', '.') . ' VNĐ';
            }
        );
    }
}
