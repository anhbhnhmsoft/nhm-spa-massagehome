<?php

namespace App\Filament\Clusters\Support\Resources\SupportTickets\Pages;

use App\Filament\Clusters\Support\Resources\SupportTickets\SupportTicketResource;
use Filament\Resources\Pages\ListRecords;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;
}
