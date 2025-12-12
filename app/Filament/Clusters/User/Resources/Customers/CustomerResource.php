<?php

namespace App\Filament\Clusters\User\Resources\Customers;

use App\Enums\UserRole;
use App\Filament\Clusters\User\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Clusters\User\Resources\Customers\Pages\EditCustomer;
use App\Filament\Clusters\User\Resources\Customers\Pages\ListCustomers;
use App\Filament\Clusters\User\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Clusters\User\Resources\Customers\Tables\CustomersTable;
use App\Filament\Clusters\User\UserCluster;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = UserCluster::class;

    protected static ?string $recordTitleAttribute = 'User';

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function getNavigationLabel(): string
    {
        return __('admin.customer.label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.customer.label');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return $query->with('wallet', 'files', 'profile', 'bookings', 'reviewWrited')
            ->where('role', UserRole::CUSTOMER->value)
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
