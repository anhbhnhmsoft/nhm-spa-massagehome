<?php

namespace App\Filament\Clusters\Marketing\Resources\MobileNotifications;

use App\Filament\Clusters\Marketing\MarketingCluster;
use App\Filament\Clusters\Marketing\Resources\MobileNotifications\Pages\ListMobileNotifications;
use App\Filament\Clusters\Marketing\Resources\MobileNotifications\Tables\MobileNotificationsTable;
use App\Models\MobileNotification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MobileNotificationResource extends Resource
{
    protected static ?string $model = MobileNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('filament.navigation.marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.mobile_notification.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.mobile_notification.label');
    }


    protected static ?string $recordTitleAttribute = 'Notification';

    public static function table(Table $table): Table
    {
        return MobileNotificationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMobileNotifications::route('/'),
        ];
    }
}
