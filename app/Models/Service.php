<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Service extends Model
{
    use SoftDeletes, HasBigIntId, HasTranslations;

    protected $translatable = [
        'name',
        'description',
    ];

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'description',
        'is_active',
        'image_url',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'category_id' => 'string',
        'is_active' => 'boolean',
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
     * Mối quan hệ với ServiceBooking
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bookings()
    {
        return $this->hasMany(ServiceBooking::class);
    }

    /**
     * Mối quan hệ với ServiceOption
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function options()
    {
        return $this->hasMany(ServiceOption::class);
    }
}
