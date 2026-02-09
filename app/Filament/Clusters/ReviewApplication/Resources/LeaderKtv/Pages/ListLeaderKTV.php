<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\LeaderKtv\Pages;

use App\Filament\Clusters\ReviewApplication\Resources\LeaderKtv\LeaderKTVResource;
use Filament\Resources\Pages\ListRecords;

class ListLeaderKTV extends ListRecords
{
    protected static string $resource = LeaderKTVResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }


    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.list');
    }
}
