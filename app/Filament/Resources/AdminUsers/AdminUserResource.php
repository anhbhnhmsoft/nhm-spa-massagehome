<?php

namespace App\Filament\Resources\AdminUsers;

use App\Enums\Admin\AdminGate;
use App\Enums\Admin\AdminRole;
use App\Filament\Clusters\HumanResource\HumanResourceCluster;
use App\Filament\Resources\AdminUsers\Pages\CreateAdminUser;
use App\Filament\Resources\AdminUsers\Pages\EditAdminUser;
use App\Filament\Resources\AdminUsers\Pages\ListAdminUsers;
use App\Filament\Resources\AdminUsers\Schemas\AdminUserForm;
use App\Filament\Resources\AdminUsers\Tables\AdminUsersTable;
use App\Models\AdminUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class AdminUserResource extends Resource
{
    protected static ?string $model = AdminUser::class;

    protected static ?string $cluster = HumanResourceCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    /**
     * Only profile managers and superadmins can manage system users.
     */
    public static function canViewAny(): bool
    {
        return Gate::allows(AdminGate::ALLOW_PROFILE);
    }

    public static function canCreate(): bool
    {
        return Gate::allows(AdminGate::ALLOW_PROFILE);
    }

    public static function canEdit(Model $record): bool
    {
        return Gate::allows(AdminGate::ALLOW_SUPER_ADMIN)
            || (Gate::allows(AdminGate::ALLOW_PROFILE) && $record->role !== AdminRole::SUPER_ADMIN);
    }

    public static function canDelete(Model $record): bool
    {
        return Gate::allows(AdminGate::ALLOW_SUPER_ADMIN)
            || (Gate::allows(AdminGate::ALLOW_PROFILE) && $record->role !== AdminRole::SUPER_ADMIN);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (! Gate::allows(AdminGate::ALLOW_SUPER_ADMIN)) {
            $query->whereIn('role', array_keys(AdminRole::managementOptions()));
        }

        return $query;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.admin_user.label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.admin_user.label');
    }

    public static function getRelations(): array
    {
        return [
        ];
    }
    public static function form(Schema $schema): Schema
    {
        return AdminUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminUsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminUsers::route('/'),
            'create' => CreateAdminUser::route('/create'),
            'edit' => EditAdminUser::route('/{record}/edit'),
        ];
    }
}
