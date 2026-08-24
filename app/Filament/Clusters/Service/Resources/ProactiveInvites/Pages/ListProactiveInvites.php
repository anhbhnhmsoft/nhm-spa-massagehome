<?php

namespace App\Filament\Clusters\Service\Resources\ProactiveInvites\Pages;

use App\Filament\Clusters\Service\Resources\ProactiveInvites\ProactiveInviteResource;
use Filament\Resources\Pages\ListRecords;

class ListProactiveInvites extends ListRecords
{
    protected static string $resource = ProactiveInviteResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
