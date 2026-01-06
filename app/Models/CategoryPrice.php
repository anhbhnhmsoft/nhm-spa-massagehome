<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
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
}
