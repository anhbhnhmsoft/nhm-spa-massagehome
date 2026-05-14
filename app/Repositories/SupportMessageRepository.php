<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\SupportMessage;
use Illuminate\Database\Eloquent\Builder;

class SupportMessageRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return SupportMessage::class;
    }

    public function queryByTicket(string|int $ticketId): Builder
    {
        return $this->query()
            ->with(['customer.profile', 'staff'])
            ->where('support_ticket_id', $ticketId);
    }
}
