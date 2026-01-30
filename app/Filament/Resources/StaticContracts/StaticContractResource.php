<?php

namespace App\Filament\Resources\StaticContracts;

use App\Filament\Resources\StaticContracts\Pages\CreateStaticContract;
use App\Filament\Resources\StaticContracts\Pages\EditStaticContract;
use App\Filament\Resources\StaticContracts\Pages\ListStaticContracts;
use App\Filament\Resources\StaticContracts\Schemas\StaticContractForm;
use App\Filament\Resources\StaticContracts\Tables\StaticContractsTable;
use App\Models\StaticContract;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StaticContractResource extends Resource
{
    protected static ?string $model = StaticContract::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocument;

    protected static ?string $recordTitleAttribute = 'Contract';

    public static function form(Schema $schema): Schema
    {
        return StaticContractForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaticContractsTable::configure($table);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.static_contract.label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.static_contract.label_plural');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaticContracts::route('/'),
            'create' => CreateStaticContract::route('/create'),
            'edit' => EditStaticContract::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery();
    }
}
