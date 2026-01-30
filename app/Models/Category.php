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
}
