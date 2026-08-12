<?php

namespace App\Enums;

enum SupportTicketEventType: string
{
    case CREATED = 'created';
    case CLAIMED = 'claimed';
    case REASSIGNED = 'reassigned';
    case SLA_WARNING = 'sla_warning';
    case SLA_BREACHED = 'sla_breached';
    case CLOSED = 'closed';
    case REOPENED = 'reopened';
}
