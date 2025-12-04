<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Core\GenerateId\HasBigIntId;

class Category extends Model
{
    use SoftDeletes, HasBigIntId;

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
}
