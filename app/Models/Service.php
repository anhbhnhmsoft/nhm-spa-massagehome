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
        'performed_count'
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

}
