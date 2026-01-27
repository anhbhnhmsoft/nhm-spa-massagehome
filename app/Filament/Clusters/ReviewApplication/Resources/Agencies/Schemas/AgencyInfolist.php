<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\Agencies\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;


class AgencyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->hidden()
            ->components([
            Section::make()
                ->schema([

                ])
        ]);
    }
}
