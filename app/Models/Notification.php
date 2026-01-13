<?php

namespace App\Models;

use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'data',
        'type',
        'status',
    ];

    protected $casts = [
        'user_id' => 'string',
        'type' => NotificationType::class,
        'status' => NotificationStatus::class,
        'data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        $this->update([
            'status' => NotificationStatus::READ,
        ]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => NotificationStatus::SENT,
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => NotificationStatus::FAILED,
        ]);
    }
}
