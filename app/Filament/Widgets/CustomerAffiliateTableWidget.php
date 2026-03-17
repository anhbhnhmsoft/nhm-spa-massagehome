<?php

namespace App\Filament\Widgets;

use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\User\Resources\Customers\Tables\CustomersTable;
use App\Repositories\UserRepository;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

/**
 * Widget hiển thị danh sách khách affiliate
 */
class CustomerAffiliateTableWidget extends TableWidget
{
    protected static bool $isLazy = true;
    public ?Model $record = null;

    protected int | string | array $columnSpan = 1;

    protected function getTableHeading(): string|Htmlable|null
    {
        return __('admin.ktv.infolist.customer_affiliate');
    }

    public function table(Table $table): Table
    {
        return CustomersTable::configure($table)
            ->defaultPaginationPageOption(5)
            ->filters([])
            ->query(function (UserRepository $repository){
                return $repository->queryUser()
                    ->with('profile')
                    ->where('role', UserRole::CUSTOMER->value)
                    ->where('referred_by_user_id', $this->record->id);
            });
    }
}
