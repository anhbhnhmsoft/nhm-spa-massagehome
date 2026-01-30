<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use App\Enums\BannerType;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Banner extends Model
{
    use HasTranslations, HasBigIntId;

    protected $table = 'banners';

    public array $translatable = [
        'image_url',
    ];

    protected $fillable = [
        'order',
        'type',
        'is_active',
        'image_url'
    ];

    protected $casts = [
        'id' => 'string',
        'type' => BannerType::class,
        'is_active' => 'boolean',
        'image_url' => 'array',
    ];



}
