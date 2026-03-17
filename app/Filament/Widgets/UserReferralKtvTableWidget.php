<?php

namespace App\Filament\Widgets;

use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\Tables\KTVsTable;
use App\Repositories\UserRepository;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
* Lấy danh sách giới thiệu KTV
 */
class UserReferralKtvTableWidget extends TableWidget
{
    protected static bool $isLazy = true;

    public ?Model $record = null;

    protected int | string | array $columnSpan = 1;

    protected function getTableHeading(): string|Htmlable|null
    {
        return __('admin.ktv.infolist.list_referral_leader');
    }

    public function table(Table $table): Table
    {
        return KTVsTable::configure($table)
            ->defaultPaginationPageOption(5)
            ->filters([])
            ->query(function (UserRepository $repository){
                return $repository->queryUser()
                    ->with('profile', 'reviewApplication')
                    ->where('role', UserRole::KTV->value)
                    ->whereHas('reviewApplication', function (Builder $query) {
                        $query->where('status', ReviewApplicationStatus::APPROVED);
                        $query->where('referrer_id', $this->record->id);
                    });
            });
    }
}
