<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatRoom extends Model
{
    protected $fillable = [
        'customer_id',
        'ktv_id',
    ];

    protected $appends = [
        'has_active_booking',
        'chat_state',
        'closed_reason',
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

    public function activeBookings()
    {
        return $this->hasMany(ServiceBooking::class, 'ktv_user_id', 'ktv_id')
            ->whereColumn('service_bookings.user_id', 'chat_rooms.customer_id')
            ->whereIn('service_bookings.status', [
                \App\Enums\BookingStatus::CONFIRMED->value,
                \App\Enums\BookingStatus::ONGOING->value,
            ]);
    }

    public function latestRelatedBooking()
    {
        return $this->hasOne(ServiceBooking::class, 'ktv_user_id', 'ktv_id')
            ->whereColumn('service_bookings.user_id', 'chat_rooms.customer_id')
            ->latestOfMany();
    }

    public function getHasActiveBookingAttribute(): bool
    {
        if (array_key_exists('has_active_booking', $this->attributes)) {
            return (bool) $this->attributes['has_active_booking'];
        }

        if ($this->relationLoaded('activeBookings')) {
            return $this->activeBookings->isNotEmpty();
        }

        return $this->activeBookings()->exists();
    }

    public function getChatStateAttribute(): string
    {
        return $this->has_active_booking ? 'active' : 'closed';
    }

    public function getClosedReasonAttribute(): ?string
    {
        return $this->has_active_booking ? null : 'booking_completed';
    }
}


