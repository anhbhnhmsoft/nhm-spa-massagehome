<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasTranslations, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'is_active',
    ];

    public $translatable = [
        'title',
        'content',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];
}
