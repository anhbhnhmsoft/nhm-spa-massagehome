<?php

namespace App\Filament\Clusters\HumanResource\Resources\Staffs;

use App\Enums\Admin\AdminGate;
use App\Enums\Admin\AdminRole;
use App\Filament\Clusters\HumanResource\HumanResourceCluster;
use App\Filament\Clusters\HumanResource\Resources\Staffs\Pages\CreateStaff;
use App\Filament\Clusters\HumanResource\Resources\Staffs\Pages\EditStaff;
use App\Filament\Clusters\HumanResource\Resources\Staffs\Pages\ListStaffs;
use App\Filament\Clusters\HumanResource\Resources\Staffs\Schemas\StaffForm;
use App\Filament\Clusters\HumanResource\Resources\Staffs\Tables\StaffsTable;
use App\Models\AdminUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class StaffResource extends Resource
{
    protected static ?string $model = AdminUser::class;

    protected static ?string $cluster = HumanResourceCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function canViewAny(): bool
    {
        return Gate::allows(AdminGate::ALLOW_ADMIN);
    }

    public static function canCreate(): bool
    {
        return Gate::allows(AdminGate::ALLOW_ADMIN);
    }

    public static function canEdit($record): bool
    {
        return Gate::allows(AdminGate::ALLOW_ADMIN);
    }

    public static function canDelete($record): bool
    {
        return Gate::allows(AdminGate::ALLOW_ADMIN);
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.staff.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.staff.label');
    }

    public static function form(Schema $schema): Schema
    {
        return StaffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaffs::route('/'),
            'create' => CreateStaff::route('/create'),
            'edit' => EditStaff::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', AdminRole::EMPLOYEE->value);
    }
}
