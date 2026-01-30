<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\KTVs\Pages;

use App\Enums\DateRangeDashboard;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\KTVResource;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\Widgets\BookingListKtv;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\Widgets\StatisticalStatsKTV;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\Widgets\TransactionKtvTable;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\Widgets\UserReferralLeaderKtvTableWidget;
use App\Filament\Components\CommonActions;
use App\Services\DashboardService;
use App\Services\PaymentService;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;

class ViewKTV extends ViewRecord
{
    protected static string $resource = KTVResource::class;

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
            BookingListKtv::make(),
            UserReferralLeaderKtvTableWidget::make(),
            TransactionKtvTable::make(),
        ];
    }
}
