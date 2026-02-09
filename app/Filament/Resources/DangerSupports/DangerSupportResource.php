<?php

namespace App\Filament\Resources\DangerSupports;

use App\Enums\DangerSupportStatus;
use App\Filament\Resources\DangerSupports\Pages\ListDangerSupports;
use App\Filament\Resources\DangerSupports\Tables\DangerSupportsTable;
use App\Models\DangerSupport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DangerSupportResource extends Resource
{
    protected static ?string $model = DangerSupport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldExclamation;

    public static function getNavigationLabel(): string
    {
        return "SOS";
    }

    public static function getModelLabel(): string
    {
        return "SOS";
    }
    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::query()->where('status', DangerSupportStatus::PENDING->value)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
    public static function table(Table $table): Table
    {
        return DangerSupportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDangerSupports::route('/'),
        ];
    }
}
