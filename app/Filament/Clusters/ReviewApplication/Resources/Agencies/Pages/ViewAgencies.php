<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\Agencies\Pages;


use App\Filament\Clusters\ReviewApplication\Resources\Agencies\AgencyResource;
use App\Filament\Clusters\ReviewApplication\Resources\Agencies\Widgets\StatisticalStatsAgency;
use App\Filament\Clusters\ReviewApplication\Resources\Agencies\Widgets\TransactionAgencyTable;
use App\Filament\Clusters\ReviewApplication\Resources\Agencies\Widgets\UserReferralLeaderAgencyTableWidget;
use App\Filament\Components\CommonActions;
use Filament\Resources\Pages\ViewRecord;

class ViewAgencies extends ViewRecord
{
    protected static string $resource = AgencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
        ];
    }

    public function getTitle(): string
    {
        return __('admin.agency.label');
    }

    public function getHeaderWidgets(): array
    {
        return [
            StatisticalStatsAgency::make(),
            TransactionAgencyTable::make(),
            UserReferralLeaderAgencyTableWidget::make(),
        ];
    }
}
