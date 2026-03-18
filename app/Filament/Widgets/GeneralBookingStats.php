<?php

namespace App\Filament\Widgets;

use App\Enums\Admin\AdminGate;
use App\Enums\BookingStatus;
use App\Enums\DateRangeDashboard;
use App\Filament\Clusters\Service\Resources\Bookings\BookingResource;
use App\Services\DashboardService;
use Filament\Schemas\Components\Section;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;

class GeneralBookingStats extends BaseWidget
{
    use InteractsWithPageFilters;

    public static function canView(): bool
    {
        return Gate::allows(AdminGate::ALLOW_FULL);
    }
    protected function getStats(): array
    {
        $dateRange = $this->pageFilters['date_range'] ? DateRangeDashboard::tryFrom($this->pageFilters['date_range']) : DateRangeDashboard::ALL;

        $dashboardService = app(DashboardService::class);

        // Get Revenue stats from GeneralStats
        $result = $dashboardService->getGeneralBookingStats($dateRange);
        if (!$result->isSuccess()) {
            return [
                Stat::make('Error', 'Unable to load data')->color('danger'),
            ];
        }
        $data = $result->getData();

        return [
            Section::make(__('dashboard.general_booking_stat.title'))
                ->columnSpanFull()
                ->columns(8)
                ->schema([
                    Stat::make(__('dashboard.general_booking_stat.total_booking'), $data['total_booking'])
                        ->url(BookingResource::getUrl('index'))
                        ->description(__('dashboard.general_booking_stat.total_booking_desc'))
                        ->color('success'),
                    Stat::make(__('dashboard.general_booking_stat.pending_booking'), $data['pending_booking'])
                        ->url(BookingResource::getUrl('index', ['filters[status][value]' => BookingStatus::PENDING->value]))
                        ->description(__('dashboard.general_booking_stat.pending_booking_desc'))
                        ->color('warning'),
                    Stat::make(__('dashboard.general_booking_stat.confirmed_booking'), $data['confirmed_booking'])
                        ->url(BookingResource::getUrl('index', ['filters[status][value]' => BookingStatus::CONFIRMED->value]))
                        ->description(__('dashboard.general_booking_stat.confirmed_booking_desc'))
                        ->color('success'),
                    Stat::make(__('dashboard.general_booking_stat.ongoing_booking'), $data['ongoing_booking'])
                        ->url(BookingResource::getUrl('index', ['filters[status][value]' => BookingStatus::ONGOING->value]))
                        ->description(__('dashboard.general_booking_stat.ongoing_booking_desc'))
                        ->color('info'),
                    Stat::make(__('dashboard.general_booking_stat.completed_booking'), $data['completed_booking'])
                        ->url(BookingResource::getUrl('index', ['filters[status][value]' => BookingStatus::COMPLETED->value]))
                        ->description(__('dashboard.general_booking_stat.completed_booking_desc'))
                        ->color('success'),
                    Stat::make(__('dashboard.general_booking_stat.waiting_cancel_booking'), $data['waiting_cancel_booking'])
                        ->url(BookingResource::getUrl('index', ['filters[status][value]' => BookingStatus::WAITING_CANCEL->value]))
                        ->description(__('dashboard.general_booking_stat.waiting_cancel_booking_desc'))
                        ->color('danger'),
                    Stat::make(__('dashboard.general_booking_stat.canceled_booking'), $data['canceled_booking'])
                        ->url(BookingResource::getUrl('index', ['filters[status][value]' => BookingStatus::CANCELED->value]))
                        ->description(__('dashboard.general_booking_stat.canceled_booking_desc'))
                        ->color('danger'),
                    Stat::make(__('dashboard.general_booking_stat.payment_failed_booking'), $data['payment_failed_booking'])
                        ->url(BookingResource::getUrl('index', ['filters[status][value]' => BookingStatus::PAYMENT_FAILED->value]))
                        ->description(__('dashboard.general_booking_stat.payment_failed_booking_desc'))
                        ->color('danger'),
                ])
        ];
    }
}
