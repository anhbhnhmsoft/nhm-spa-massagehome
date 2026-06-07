<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use App\Enums\BookingApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingApplication extends Model
{
    use HasBigIntId;

    protected $fillable = [
        'booking_id',
        'ktv_id',
        'status',
        'applied_at',
        'selected_at',
        'removed_reason',
    ];

    protected $casts = [
        'id' => 'string',
        'booking_id' => 'string',
        'ktv_id' => 'string',
        'status' => 'integer',
        'applied_at' => 'datetime',
        'selected_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class, 'booking_id');
    }

    public function ktv(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ktv_id');
    }

    public function statusEnum(): ?BookingApplicationStatus
    {
        return BookingApplicationStatus::tryFrom((int) $this->status);
    }
}
