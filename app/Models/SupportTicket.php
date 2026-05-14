<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use App\Enums\SupportTicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasBigIntId;

    protected $table = 'support_tickets';

    protected $fillable = [
        'customer_id',
        'category_id',
        'assigned_staff_id',
        'latest_booking_id',
        'room_id',
        'status',
        'last_message_at',
    ];

    protected $casts = [
        'id' => 'string',
        'customer_id' => 'string',
        'category_id' => 'string',
        'assigned_staff_id' => 'string',
        'latest_booking_id' => 'string',
        'room_id' => 'string',
        'status' => 'integer',
        'last_message_at' => 'datetime',
    ];

    public function setStatusAttribute(mixed $value): void
    {
        if ($value instanceof SupportTicketStatus) {
            $this->attributes['status'] = $value->dbValue();
            return;
        }

        if (is_numeric($value)) {
            $this->attributes['status'] = (int) $value;
            return;
        }

        $enum = SupportTicketStatus::tryFrom((string) $value);
        $this->attributes['status'] = $enum?->dbValue() ?? SupportTicketStatus::PENDING->dbValue();
    }

    public function statusEnum(): SupportTicketStatus
    {
        return SupportTicketStatus::fromDbValue($this->attributes['status'] ?? $this->status ?? null);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'assigned_staff_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SupportCategory::class, 'category_id');
    }

    public function latestBooking(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class, 'latest_booking_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'support_ticket_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(SupportMessage::class, 'support_ticket_id')->latestOfMany();
    }
}
