<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class Message extends Model
{
    use HasTranslations;

    protected $fillable = [
        'room_id',
        'sender_by',
        'content',
        'content_translated',
        'seen_at',
        'temp_id',
    ];

    protected array $translatable = [
        'content_translated',
    ];

    protected $casts = [
        'id' => 'string',
        'room_id' => 'string',
        'sender_by' => 'string',
        'seen_at' => 'datetime',
        'temp_id' => 'string',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class, 'room_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_by');
    }
}


