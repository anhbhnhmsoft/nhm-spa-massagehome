<?php

namespace App\Filament\Resources\AdminUsers;

use App\Enums\Admin\AdminRole;
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

class AdminUserResource extends Resource
{
    protected static ?string $model = AdminUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    /**
     * Chỉ có admin mới có thể xem danh sách admin user
     * @return bool
     */
    public static function canViewAny(): bool
    {
        return auth('web')->user()->role === AdminRole::ADMIN;
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
