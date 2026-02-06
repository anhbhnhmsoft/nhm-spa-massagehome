<?php

namespace App\Filament\Components;

use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class CommonFields
{
    public static function IdColumn()
    {
        return TextColumn::make('id')
            ->searchable()
            ->width('80px')
            ->label(__('admin.common.table.id'));
    }

    public static function SelectReferrerIdForKTVAndAgency()
    {
        return Select::make('referrer_id')
            ->label(__('admin.ktv_apply.fields.agency'))
            ->relationship(
                name: 'referrer',
                titleAttribute: 'name',
                modifyQueryUsing: function (Builder $query, $get) {
                    $currentId = $get('user_id') ?? null;
                    $query->whereIn('role', [UserRole::AGENCY->value, UserRole::KTV->value])
                        ->where('is_active', true)
                        ->whereHas('reviewApplication', function ($query) {
                            $query->where('status', ReviewApplicationStatus::APPROVED);
                        });
                    if ($currentId) {
                        $query->where('id', '!=', $currentId);
                    }
                    return $query;
                }
            )
            ->searchable() // Filament sẽ tự động search theo titleAttribute (name)
            ->preload() // Load trước một ít dữ liệu để chọn nhanh
            ->disabled(fn($livewire) => $livewire instanceof ViewRecord)
            ->columnSpan(1);
    }

}
