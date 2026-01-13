<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;
    use HasBigIntId;

    protected $fillable = [
        'room_id',
        'sender_by',
        'content',
        'seen_at',
        'temp_id',
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


