<?php

namespace App\Filament\Clusters\User\Resources\Reviews\Pages;

use App\Filament\Clusters\User\Resources\Reviews\ReviewResource;
use Filament\Resources\Pages\ListRecords;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;
}
