<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use SoftDeletes, HasFactory, HasBigIntId;

    protected $table = 'reviews';

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',    // ID người nhận review (Provider)
        'review_by',  // ID người viết review (Customer)
        'rating',
        'comment',
        'hidden',
        'review_at'
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'review_by' => 'string',
        'rating' => 'integer',
        'review_at' => 'datetime',
        'hidden' => 'boolean',
    ];

    // Người nhận đánh giá (Provider)
    public function recipient()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Người viết đánh giá (Customer)
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'review_by');
    }
}
