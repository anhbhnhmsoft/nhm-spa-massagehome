<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\LeaderKtv\Pages;

use App\Filament\Clusters\ReviewApplication\Resources\LeaderKtv\Widgets\LeaderBookingListKtv;
use App\Filament\Clusters\ReviewApplication\Resources\LeaderKtv\Widgets\StatisticalStatsKTV;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\Widgets\TransactionKtvTable;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\Widgets\UserReferralLeaderKtvTableWidget;
use App\Filament\Clusters\ReviewApplication\Resources\LeaderKtv\LeaderKTVResource;
use App\Filament\Components\CommonActions;
use Filament\Resources\Pages\ViewRecord;

class ViewLeaderKTV extends ViewRecord
{
    protected static string $resource = LeaderKTVResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
        ];
    }

    public function getTitle(): string
    {
        return __('admin.ktv.label');
    }

    public function getContentTabLabel(): ?string
    {
        return __('admin.ktv.infolist.label_tab');
    }

    protected function getFooterWidgets(): array
    {
        return [
            StatisticalStatsKTV::make(),
            LeaderBookingListKtv::make(),
            UserReferralLeaderKtvTableWidget::make(),
            TransactionKtvTable::make(),
        ];
    }
}
