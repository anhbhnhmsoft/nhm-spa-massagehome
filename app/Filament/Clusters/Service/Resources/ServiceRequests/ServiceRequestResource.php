<?php

namespace App\Filament\Clusters\Service\Resources\ServiceRequests;

use App\Enums\Admin\AdminGate;
use App\Filament\Clusters\Service\Resources\ServiceRequests\Pages\ListServiceRequests;
use App\Filament\Clusters\Service\Resources\ServiceRequests\Tables\ServiceRequestsTable;
use App\Models\ServiceRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    public static function canViewAny(): bool
    {
        return Gate::allows(AdminGate::ALLOW_PROFILE);
    }

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return __('filament.navigation.service');
    }

    protected static ?string $recordTitleAttribute = 'ServiceRequest';

    public static function getNavigationLabel(): string
    {
        return __('admin.service_request.nav');
    }

    public static function getModelLabel(): string
    {
        return __('admin.service_request.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.service_request.plural');
    }

    public static function table(Table $table): Table
    {
        return ServiceRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceRequests::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->with([
                'customer',
                'service',
                'cskh',
                'proposals.ktv',
            ]);
    }
}
