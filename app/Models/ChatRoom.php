<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatRoom extends Model
{
    use HasFactory, HasBigIntId;

    protected $fillable = [
        'customer_id',
        'ktv_id',
    ];

    protected $casts = [
        'id' => 'string',
        'customer_id' => 'string',
        'ktv_id' => 'string',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function ktv(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ktv_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'room_id');
    }

    // Lấy tin nhắn mới nhất trong phòng chat
    public function latestMessage()
    {
        return $this->hasOne(Message::class, 'room_id')->latestOfMany();
    }
}



