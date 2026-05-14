<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use App\Enums\SupportMessageSenderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    use HasBigIntId;

    protected $table = 'support_messages';

    protected $fillable = [
        'support_ticket_id',
        'sender_type',
        'sender_user_id',
        'sender_admin_id',
        'content',
        'temp_id',
        'seen_at',
    ];

    protected $casts = [
        'id' => 'string',
        'support_ticket_id' => 'string',
        'sender_user_id' => 'string',
        'sender_admin_id' => 'string',
        'sender_type' => 'integer',
        'temp_id' => 'string',
        'seen_at' => 'datetime',
    ];

    public function setSenderTypeAttribute(mixed $value): void
    {
        if ($value instanceof SupportMessageSenderType) {
            $this->attributes['sender_type'] = $value->dbValue();
            return;
        }

        if (is_numeric($value)) {
            $this->attributes['sender_type'] = (int) $value;
            return;
        }

        $enum = SupportMessageSenderType::tryFrom((string) $value);
        $this->attributes['sender_type'] = $enum?->dbValue() ?? SupportMessageSenderType::SYSTEM->dbValue();
    }

    public function senderTypeEnum(): SupportMessageSenderType
    {
        return SupportMessageSenderType::fromDbValue($this->attributes['sender_type'] ?? $this->sender_type ?? null);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'sender_admin_id');
    }
}
