<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\KTVs\Widgets;

use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\Tables\KTVsTable;
use App\Repositories\UserRepository;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserReferralLeaderKtvTableWidget extends TableWidget
{
    public ?Model $record = null;

    protected int | string | array $columnSpan = 2;

    protected function getTableHeading(): string|Htmlable|null
    {
        return __('admin.ktv.infolist.list_referral_leader');
    }

    public function table(Table $table): Table
    {
        $userRepository = app(UserRepository::class);
        return KTVsTable::configure($table)
            ->filters([])
            ->query(function () use ($userRepository) {
                return $userRepository->queryUser()->with('profile', 'reviewApplication')
                    ->whereIn('role', [UserRole::KTV->value, UserRole::CUSTOMER->value])
                    ->whereHas('reviewApplication', function (Builder $query) {
                        $query->whereIn('status', ReviewApplicationStatus::values());
                        $query->where('role', UserRole::KTV->value);
                        $query->where('referrer_id', $this->record->id);
                    })
                    ->withoutGlobalScopes([
                        SoftDeletingScope::class,
                    ]);
            });
    }
}
