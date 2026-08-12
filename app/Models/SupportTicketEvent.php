<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SupportTicketEvent extends Model
{
    use HasBigIntId;

    protected $table = 'support_ticket_events';

    protected $fillable = [
        'support_ticket_id', 'actor_admin_id', 'event_type',
        'from_staff_id', 'to_staff_id', 'metadata',
    ];

    protected $casts = [
        'id' => 'string',
        'support_ticket_id' => 'string',
        'actor_admin_id' => 'string',
        'from_staff_id' => 'string',
        'to_staff_id' => 'string',
        'metadata' => 'array',
    ];

    public function ticket(): BelongsTo { return $this->belongsTo(SupportTicket::class, 'support_ticket_id'); }
    public function actor(): BelongsTo { return $this->belongsTo(AdminUser::class, 'actor_admin_id'); }
    public function fromStaff(): BelongsTo { return $this->belongsTo(AdminUser::class, 'from_staff_id'); }
    public function toStaff(): BelongsTo { return $this->belongsTo(AdminUser::class, 'to_staff_id'); }
}
