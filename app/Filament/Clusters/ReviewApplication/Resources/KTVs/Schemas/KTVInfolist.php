<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\KTVs\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KTVInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ktv.infolist.info_label'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('reviewApplication.nickname')
                            ->label(__('admin.ktv_apply.fields.nickname'))
                            ->placeholder('-'),
                        IconEntry::make('reviewApplication.is_priority')
                            ->label(__('admin.ktv_apply.fields.is_priority'))
                            ->boolean(),
                        IconEntry::make('reviewApplication.is_leader')
                            ->label(__('admin.ktv.infolist.is_leader'))
                            ->boolean(),
                    ]),
            ]);
    }
}
