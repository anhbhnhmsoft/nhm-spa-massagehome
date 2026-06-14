<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class SupportCategory extends Model
{
    use HasBigIntId, HasTranslations;

    protected $table = 'support_categories';

    protected $fillable = [
        'name',
        'description',
        'message',
        'position',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'position' => 'integer',
        'is_active' => 'boolean',
    ];

    protected array $translatable = [
        'name',
        'description',
        'message',
    ];
}
