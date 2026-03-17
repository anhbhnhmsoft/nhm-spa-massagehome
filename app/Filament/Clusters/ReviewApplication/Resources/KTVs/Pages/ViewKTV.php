<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\KTVs\Pages;

use App\Filament\Clusters\ReviewApplication\Resources\KTVs\KTVResource;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\Widgets\BookingListTableWidget;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\Widgets\StatisticalStatsKTV;
use App\Filament\Components\CommonActions;
use App\Filament\Widgets\CustomerAffiliateTableWidget;
use App\Filament\Widgets\TransactionListTableWidget;
use App\Filament\Widgets\UserReferralKtvTableWidget;
use App\Filament\Widgets\WalletStats;
use Filament\Resources\Pages\ViewRecord;


class ViewKTV extends ViewRecord
{
    protected static string $resource = KTVResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Quay lại
            CommonActions::backAction(static::getResource()),
            // Tạo đánh giá ảo
            CommonActions::reviewVirtualAction(),
            // Cập nhật số lượng dịch vụ đã thực hiện (buff ảo)
            CommonActions::buffServiceAction(),
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
            // Thống kê wallet
            WalletStats::make(),
            // Thông kê
            StatisticalStatsKTV::make(),
            // Danh sách booking của KTV
            BookingListTableWidget::make(),
            // Giới thiệu KTV
            UserReferralKtvTableWidget::make(),
            // Khách hàng affiliate
            CustomerAffiliateTableWidget::make(),
            // Danh sách giao dịch
            TransactionListTableWidget::make(),
        ];
    }
}
