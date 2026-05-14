<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\SupportTicket;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return SupportTicket::class;
    }

    public function queryWithRelations(): Builder
    {
        return $this->query()->with([
            'customer.profile',
            'assignedStaff',
            'category',
            'latestBooking.user.profile',
            'latestBooking.service',
            'latestMessage.customer',
            'latestMessage.staff',
        ]);
    }
}
