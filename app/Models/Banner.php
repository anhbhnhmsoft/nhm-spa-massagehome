<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Banner extends Model
{
    use HasFactory, HasTranslations, HasBigIntId;

    protected $table = 'banners';

    public array $translatable = [
        'image_url',
    ];

    protected $fillable = [
        'order',
        'is_active',
        'image_url'
    ];

    protected $casts = [
        'id' => 'string',
        'is_active' => 'boolean',
    ];



}
