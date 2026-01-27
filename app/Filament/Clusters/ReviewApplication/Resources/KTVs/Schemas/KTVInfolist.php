<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\KTVs\Schemas;

use App\Enums\Gender;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KTVInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->hidden()
            ->components([
                Section::make()->schema([])
            ]);
    }
}
