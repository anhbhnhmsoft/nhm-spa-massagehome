<?php

namespace App\Filament\Components;

use Filament\Tables\Columns\TextColumn;

class CommonColumns
{
    public static function IdColumn()
    {
        return TextColumn::make('id')
            ->searchable()
            ->width('80px')
            ->label(__('admin.common.table.id'));
    }

}
