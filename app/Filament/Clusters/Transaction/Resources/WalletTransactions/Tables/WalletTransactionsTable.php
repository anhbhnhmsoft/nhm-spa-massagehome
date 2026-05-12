<?php

namespace App\Filament\Clusters\Transaction\Resources\WalletTransactions\Tables;

use App\Enums\UserRole;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Filament\Clusters\ReviewApplication\Resources\Agencies\AgencyResource;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\KTVResource;
use App\Filament\Clusters\User\Resources\Customers\CustomerResource;
use App\Filament\Components\CommonFields;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with(['wallet.user']);
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                CommonFields::IdColumn()
                    ->searchable(),

                TextColumn::make('wallet.user.name')
                    ->label(__('admin.transaction.fields.user'))
                    ->searchable(query: function ($query, $search) {
                        $query->whereHas('wallet.user', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                    })
                    ->color('primary') // Chuyển chữ sang màu xanh
                    ->weight('bold')
                    ->url(function ($record) {
                        $user = $record->wallet->user;
                        $role = $user->role;
                        return match ($role) {
                            UserRole::KTV->value => KTVResource::getUrl('view', ['record' => $user]),
                            UserRole::CUSTOMER->value => CustomerResource::getUrl('edit', ['record' => $user]),
                            UserRole::AGENCY->value => AgencyResource::getUrl('view', ['record' => $user]),
                            default => null,
                        };
                    })
                    ->formatStateUsing(function ($state, $record) {
                        $user = $record->wallet->user;
                        $contact = $user->phone ?? $user->email;
                        return "$user->name - ($contact)";
                    })
                    ->description(function ($record) {
                        $userRole = $record->wallet->user->role;
                        return UserRole::from($userRole)->label() ?? "";
                    }),
                TextColumn::make('type')
                    ->label(__('admin.transaction.fields.type'))
                    ->formatStateUsing(fn($state) => WalletTransactionType::tryFrom($state)?->label())
                    ->badge(),
                TextColumn::make('transaction_code')
                    ->label(__('admin.transaction.fields.code'))
                    ->searchable(),
                TextColumn::make('point_amount')
                    ->label(__('admin.transaction.fields.amount'))
                    ->numeric(2),
                TextColumn::make('status')
                    ->label(__('admin.transaction.fields.status'))
                    ->formatStateUsing(fn($state) => WalletTransactionStatus::tryFrom($state)?->label() ?? "")
                    ->color(fn($state) => match (WalletTransactionStatus::tryFrom($state)) {
                        WalletTransactionStatus::PENDING => 'warning',
                        WalletTransactionStatus::COMPLETED => 'success',
                        WalletTransactionStatus::FAILED, WalletTransactionStatus::CANCELLED => 'danger',
                        WalletTransactionStatus::REFUNDED => 'info',
                        default => 'secondary',
                    })
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('admin.transaction.fields.created_at'))
                    ->dateTime("d-M-Y H:i")
                    ->sortable(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(5)
            ->filters([
                SelectFilter::make('type')
                    ->label(__('admin.transaction.fields.type'))
                    ->options(WalletTransactionType::toOptions()),
                SelectFilter::make('status')
                    ->label(__('admin.transaction.fields.status'))
                    ->options(WalletTransactionStatus::toOptions()),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.transaction.empty_state.heading'));
    }
}
